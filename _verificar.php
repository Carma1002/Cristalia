<?php
/**
 * Cristalia · Verificación de estructura de carpetas
 * Se ejecuta antes de cargar el sistema. Si faltan carpetas (por ejemplo, porque
 * quedaron dentro de "2_app/app" al descomprimir), muestra cómo corregirlo
 * en vez de un error de PHP.
 */
(function (): void {
    $raiz = __DIR__;
    $requeridos = [
        'app'      => 'app/bootstrap.php',
        'assets'   => 'assets/css/app.css',
        'database' => 'database/schema.sql',
        'uploads'  => 'uploads',
    ];
    $faltan = [];
    foreach ($requeridos as $carpeta => $prueba) {
        if (!file_exists("$raiz/$prueba")) $faltan[] = $carpeta;
    }
    if (!$faltan) return;

    // Buscar dónde quedó cada carpeta faltante (uno o dos niveles más adentro, ej. "2_app/app")
    $encontradas = [];
    foreach ($faltan as $carpeta) {
        $resto = substr($requeridos[$carpeta], strlen($carpeta));
        foreach (array_merge(glob("$raiz/*/$carpeta", GLOB_ONLYDIR) ?: [], glob("$raiz/*/*/$carpeta", GLOB_ONLYDIR) ?: []) as $cand) {
            if (file_exists($cand . $resto)) { $encontradas[$carpeta] = substr($cand, strlen($raiz) + 1); break; }
        }
    }

    http_response_code(500);
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $sep = DIRECTORY_SEPARATOR;
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>Cristalia · Faltan carpetas</title><style>body{font-family:system-ui,Segoe UI,Arial,sans-serif;max-width:760px;margin:40px auto;padding:0 16px;color:#222;line-height:1.5}'
       . 'h1{color:#b02a37;font-size:1.5rem}code,pre{background:#f3f4f6;border-radius:4px;padding:2px 6px}pre{padding:12px;overflow:auto}'
       . '.ok{color:#198754}.no{color:#b02a37}li{margin:4px 0}.caja{border:1px solid #ddd;border-radius:8px;padding:12px 16px;margin:16px 0}</style></head><body>';
    echo '<h1>Cristalia no encuentra algunas carpetas</h1>';
    echo '<p>El sistema está en <code>' . $h($raiz) . '</code>, pero dentro de esa carpeta faltan:</p><ul>';
    foreach ($requeridos as $c => $_) {
        $ok = !in_array($c, $faltan, true);
        echo '<li class="' . ($ok ? 'ok' : 'no') . '">' . ($ok ? '✔' : '✘') . ' <code>' . $h($c) . '</code>'
           . (!$ok && isset($encontradas[$c]) ? ' — está en <code>' . $h(str_replace('/', $sep, $encontradas[$c])) . '</code>' : '') . '</li>';
    }
    echo '</ul>';
    if ($encontradas) {
        echo '<div class="caja"><strong>Cómo corregirlo:</strong> mueva cada carpeta marcada con ✘ para que quede directamente al lado de <code>index.php</code>. '
           . 'Después borre las carpetas vacías que sobren (por ejemplo <code>2_app</code>, <code>3_assets</code>…).</div>';
    }
    echo '<p>La estructura correcta es:</p><pre>' . $h(basename($raiz)) . $sep . "\n"
       . "├── app$sep\n├── assets$sep\n├── database$sep\n├── uploads$sep\n├── index.php\n└── install.php</pre>";
    echo '<p>Luego recargue esta página.</p></body></html>';
    exit;
})();
