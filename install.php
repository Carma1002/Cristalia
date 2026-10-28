<?php
/**
 * Cristalia · Instalador web
 * Crea las tablas, datos base, sucursal principal y usuario administrador,
 * y genera app/config.php. Se bloquea automáticamente una vez instalado.
 */
require __DIR__ . '/_verificar.php';
require __DIR__ . '/app/bootstrap.php';

$errores = [];
$listo = false;
$configTexto = null;
$claveDemo = null;

if (APP_INSTALLED) {
    $bloqueado = true;
} else {
    $bloqueado = false;
    $req = [
        'PHP 8.1 o superior (actual: ' . PHP_VERSION . ')' => PHP_VERSION_ID >= 80100,
        'Extensión PDO MySQL' => extension_loaded('pdo_mysql'),
        'Extensión mbstring' => extension_loaded('mbstring'),
        'Carpeta app/ con permiso de escritura' => is_writable(APP_PATH),
        'Carpeta uploads/ con permiso de escritura' => is_writable(ROOT_PATH . '/uploads') || @mkdir(ROOT_PATH . '/uploads', 0755, true),
    ];

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $db = [
            'host' => trim($_POST['db_host'] ?? ''), 'port' => (int)($_POST['db_port'] ?? 3306),
            'name' => trim($_POST['db_name'] ?? ''), 'user' => trim($_POST['db_user'] ?? ''), 'pass' => (string)($_POST['db_pass'] ?? ''),
        ];
        $adminNombre = trim($_POST['admin_nombre'] ?? '');
        $adminUser = strtolower(trim($_POST['admin_usuario'] ?? ''));
        $adminPass = (string)($_POST['admin_password'] ?? '');
        $empresa = trim($_POST['empresa'] ?? '');
        $sucursal = trim($_POST['sucursal'] ?? '');
        $demo = !empty($_POST['demo']);

        if (!$db['host'] || !$db['name'] || !$db['user']) $errores[] = 'Complete los datos de conexión a la base de datos.';
        if (mb_strlen($adminNombre) < 3) $errores[] = 'Indique el nombre del administrador.';
        if (!preg_match('/^[a-z0-9._-]{3,40}$/', $adminUser)) $errores[] = 'Usuario administrador inválido.';
        if (strlen($adminPass) < 8 || !preg_match('/[A-Za-z]/', $adminPass) || !preg_match('/\d/', $adminPass)) $errores[] = 'La contraseña del administrador debe tener 8+ caracteres con letras y números.';
        if (!$empresa || !$sucursal) $errores[] = 'Indique la empresa y la sucursal principal.';

        if (!$errores) {
            try {
                $GLOBALS['config'] = ['db' => $db];
                // En localhost (XAMPP) la base se crea sola si no existe; en InfinityFree ya viene creada
                if (!preg_match('/^[A-Za-z0-9_]+$/', $db['name'])) throw new RuntimeException('El nombre de la base solo puede tener letras, números y guion bajo.');
                try {
                    $srv = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $db['host'], $db['port']), $db['user'], $db['pass'],
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    $srv->exec("CREATE DATABASE IF NOT EXISTS `{$db['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                } catch (PDOException) {
                    // Sin permiso para crear bases (hosting compartido): se usa la existente
                }
                $pdo = DB::connect($db);
                DB::setPdo($pdo);
                foreach (['schema.sql', 'seed.sql'] as $f) {
                    foreach (Respaldo::dividir(file_get_contents(ROOT_PATH . "/database/$f")) as $sql) $pdo->exec($sql);
                }
                DB::run("UPDATE configuracion SET valor = ? WHERE clave = 'empresa_nombre'", [$empresa]);
                $sid = DB::insert('sucursales', ['codigo' => 'PRI', 'nombre' => $sucursal, 'direccion' => null]);
                $uid = DB::insert('usuarios', ['nombre' => $adminNombre, 'usuario' => $adminUser,
                    'password_hash' => password_hash($adminPass, PASSWORD_DEFAULT), 'rol_id' => 1, 'sucursal_id' => null]);
                $_SESSION['uid'] = $uid;
                $_SESSION['last_seen'] = time();
                Bitacora::log('administracion', 'instalacion', 'Sistema instalado' . ($demo ? ' con datos de demostración' : ''), $uid);
                if ($demo) {
                    @set_time_limit(300);
                    $claveDemo = bin2hex(random_bytes(6));
                    require ROOT_PATH . '/database/demo.php';
                    cargar_demo($claveDemo);
                }
                $configTexto = "<?php\n// Generado por el instalador de " . APP_NAME . ' el ' . date('Y-m-d H:i') . "\n// NO subir este archivo a GitHub (contiene credenciales).\nreturn " . var_export([
                    'db' => $db, 'timezone' => 'America/Caracas', 'debug' => false,
                ], true) . ";\n";
                if (@file_put_contents(APP_PATH . '/config.php', $configTexto) !== false) $configTexto = null;
                $_SESSION = [];
                $listo = true;
            } catch (Throwable $e) {
                $errores[] = 'Error durante la instalación: ' . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0F536B">
  <title>Instalación · <?= APP_NAME ?></title>
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
  <link href="assets/vendor/fonts/fonts.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/app.css?v=<?= APP_VERSION ?>" rel="stylesheet">
  <style>
    .inst-hero { position: relative; overflow: hidden; background: var(--c-azul); color: #fff; padding: 40px 24px 90px; text-align: center; }
    .inst-hero::before { content: ''; position: absolute; inset: -10%; background: url('assets/img/patron-facetas.svg') center / cover; animation: c-drift 60s linear infinite alternate; }
    .inst-hero > * { position: relative; }
    .inst-wrap { max-width: 820px; margin: -60px auto 40px; padding: 0 16px; position: relative; }
    .req { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 6px 16px; font-size: .85rem; }
    .req i { margin-right: 6px; }
  </style>
</head>
<body>
<div class="inst-hero">
  <img src="assets/img/cristalia-vertical-blanco.svg" alt="Cristalia" style="width:150px" class="reveal">
  <p class="mt-3 mb-0 reveal" style="--d:2;color:rgba(255,255,255,.8)">Instalación del sistema · Claridad para decidir</p>
</div>
<div class="inst-wrap">

  <?php if ($bloqueado): ?>
    <div class="c-card c-card__body reveal text-center">
      <div class="c-empty__icon"><i class="bi bi-shield-check"></i></div>
      <h1 class="h4">El sistema ya está instalado</h1>
      <p class="text-muted">Por seguridad el instalador está bloqueado. Para reinstalar, elimine el archivo <code>app/config.php</code> del servidor.</p>
      <a class="btn btn-primary" href="index.php">Ir al sistema <i class="bi bi-arrow-right"></i></a>
    </div>

  <?php elseif ($listo): ?>
    <div class="c-card c-card__body reveal text-center">
      <div class="c-empty__icon" style="background:var(--c-ok-bg);color:var(--c-ok)"><i class="bi bi-check2-circle"></i></div>
      <h1 class="h4">¡Instalación completada!</h1>
      <p class="text-muted">Ya puede entrar con el usuario administrador que acaba de crear.</p>
      <?php if ($configTexto): ?>
        <div class="alert alert-warning text-start"><i class="bi bi-exclamation-triangle"></i><div>No se pudo escribir <code>app/config.php</code> automáticamente. Cree ese archivo con este contenido usando el administrador de archivos del hosting:</div></div>
        <pre class="text-start p-3 rounded-3 small" style="background:var(--c-profundo);color:#fff"><?= htmlspecialchars($configTexto) ?></pre>
      <?php endif; ?>
      <?php if (!empty($demo)): ?>
        <div class="alert alert-info text-start"><i class="bi bi-key"></i><div>Se cargaron datos de demostración. La contraseña temporal para estos usuarios es <code><?= htmlspecialchars((string)$claveDemo, ENT_QUOTES, 'UTF-8') ?></code>. Anótala ahora; se genera aleatoriamente para esta instalación. Usuarios: <code>ventas</code>, <code>almacen</code>, <code>ventas.val</code> y <code>almacen.mcy</code>.</div></div>
      <?php endif; ?>
      <a class="btn btn-primary btn-lg" href="index.php">Iniciar sesión <i class="bi bi-arrow-right"></i></a>
    </div>

  <?php else: ?>
    <div class="c-card c-card__body mb-3 reveal">
      <h2 class="c-section-title"><i class="bi bi-hdd-rack" style="color:var(--c-cian)"></i> Requisitos del servidor</h2>
      <div class="req">
        <?php foreach ($req as $k => $ok): ?><div><i class="bi bi-<?= $ok ? 'check-circle-fill' : 'x-circle-fill' ?>" style="color:<?= $ok ? 'var(--c-ok)' : 'var(--c-bad)' ?>"></i><?= $k ?></div><?php endforeach; ?>
      </div>
    </div>
    <?php foreach ($errores as $e): ?><div class="alert alert-danger reveal"><i class="bi bi-exclamation-circle"></i><div><?= htmlspecialchars($e) ?></div></div><?php endforeach; ?>
    <form method="post" class="c-card c-card__body reveal" style="--d:2">
      <h2 class="c-section-title"><span class="num-step">1</span> Base de datos MySQL</h2>
      <p class="small text-muted"><strong>En localhost (XAMPP):</strong> host <code>localhost</code>, usuario <code>root</code>, contraseña vacía y nombre <code>cristalia</code> (se crea automáticamente).<br><strong>En InfinityFree:</strong> Panel de control → <em>MySQL Databases</em>. Allí aparecen el host (ej. <code>sql123.infinityfree.com</code>), el nombre (ej. <code>if0_12345678_cristalia</code>), el usuario y la contraseña.</p>
      <div class="row g-3 mb-4">
        <div class="col-8"><label class="form-label">Host</label><input name="db_host" class="form-control" required value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>"></div>
        <div class="col-4"><label class="form-label">Puerto</label><input name="db_port" class="form-control" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>"></div>
        <div class="col-md-4"><label class="form-label">Nombre de la base</label><input name="db_name" class="form-control" required value="<?= htmlspecialchars($_POST['db_name'] ?? 'cristalia') ?>"></div>
        <div class="col-md-4"><label class="form-label">Usuario</label><input name="db_user" class="form-control" required value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>"></div>
        <div class="col-md-4"><label class="form-label">Contraseña</label><input name="db_pass" type="password" class="form-control"></div>
      </div>
      <h2 class="c-section-title"><span class="num-step">2</span> Empresa</h2>
      <div class="row g-3 mb-4">
        <div class="col-md-7"><label class="form-label">Razón social</label><input name="empresa" class="form-control" required value="<?= htmlspecialchars($_POST['empresa'] ?? 'Empresa de autopartes') ?>"></div>
        <div class="col-md-5"><label class="form-label">Sucursal principal</label><input name="sucursal" class="form-control" required value="<?= htmlspecialchars($_POST['sucursal'] ?? 'Sucursal principal') ?>"></div>
      </div>
      <h2 class="c-section-title"><span class="num-step">3</span> Usuario administrador</h2>
      <div class="row g-3 mb-4">
        <div class="col-md-4"><label class="form-label">Nombre</label><input name="admin_nombre" class="form-control" required value="<?= htmlspecialchars($_POST['admin_nombre'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Usuario</label><input name="admin_usuario" class="form-control" required value="<?= htmlspecialchars($_POST['admin_usuario'] ?? 'admin') ?>"></div>
        <div class="col-md-4"><label class="form-label">Contraseña</label><input name="admin_password" type="password" class="form-control" required minlength="8"></div>
      </div>
      <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="demo" value="1" id="demo" <?= !empty($_POST['demo']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="demo">Cargar datos de demostración (3 sucursales, 24 productos, 12 meses de ventas). Útil para la presentación.</label></div>
      <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle"></i><div>Si la base de datos ya tiene tablas de <?= APP_NAME ?>, serán reemplazadas.</div></div>
      <button class="btn btn-primary btn-lg w-100">Instalar <?= APP_NAME ?></button>
    </form>
  <?php endif; ?>
</div>
<script src="assets/js/app.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
