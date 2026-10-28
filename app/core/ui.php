<?php
/**
 * Componentes de interfaz reutilizables (navegación, encabezados, estados, vacíos).
 */

/** Permiso: 'auth', una clave o un arreglo de claves (basta con una). */
function permitido(string|array $perm): bool
{
    if ($perm === 'auth') return Auth::check();
    foreach ((array)$perm as $p) if (Auth::can($p)) return true;
    return false;
}

/** Secciones visibles para el usuario con su enlace y estado activo. */
function nav_secciones(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $ruta = (string)($_GET['r'] ?? 'dashboard');
    $out = [];
    $mejor = ['len' => -1, 'sec' => null, 'tab' => null];
    foreach (require APP_PATH . '/nav.php' as $s) {
        $tabs = array_values(array_filter($s['tabs'], fn($t) => permitido($t['perm'])));
        if (!$tabs) continue;
        foreach ($tabs as $t) {
            foreach ($t['match'] as $m) {
                if (($ruta === $m || str_starts_with($ruta, $m . '/')) && strlen($m) > $mejor['len']) {
                    $mejor = ['len' => strlen($m), 'sec' => $s['key'], 'tab' => $t['r']];
                }
            }
        }
        $s['tabs'] = $tabs;
        $s['href'] = url($tabs[0]['r']);
        $out[$s['key']] = $s;
    }
    foreach ($out as $k => &$s) {
        $s['activa'] = $k === $mejor['sec'];
        foreach ($s['tabs'] as &$t) $t['activa'] = $s['activa'] && $t['r'] === $mejor['tab'];
        unset($t);
    }
    unset($s);
    return $cache = $out;
}

/** Pestañas de la sección actual (sólo si hay más de una). */
function ui_tabs(): string
{
    foreach (nav_secciones() as $s) {
        if (!$s['activa'] || count($s['tabs']) < 2) continue;
        $h = '<nav class="c-tabs" aria-label="' . e($s['label']) . '">';
        foreach ($s['tabs'] as $t) {
            $h .= '<a class="c-tab' . ($t['activa'] ? ' is-active' : '') . '" href="' . url($t['r']) . '">'
                . (!empty($t['icon']) ? '<i class="bi bi-' . e($t['icon']) . '"></i>' : '') . '<span>' . e($t['label']) . '</span></a>';
        }
        return $h . '</nav>';
    }
    return '';
}

/**
 * Encabezado de página: título, subtítulo, acciones y botón "volver" opcional.
 */
function ui_header(string $titulo, string $subtitulo = '', string $acciones = '', ?string $volver = null): string
{
    $h = '<header class="c-page-head reveal">';
    $h .= '<div class="c-page-head__text">';
    if ($volver) $h .= '<a href="' . e($volver) . '" class="c-back" aria-label="Volver"><i class="bi bi-arrow-left"></i></a>';
    $h .= '<div><h1 class="c-title">' . e($titulo) . '</h1>' . ($subtitulo !== '' ? '<p class="c-subtitle">' . $subtitulo . '</p>' : '') . '</div></div>';
    if ($acciones !== '') $h .= '<div class="c-page-head__actions">' . $acciones . '</div>';
    return $h . '</header>';
}

/** Estado de pedido / venta / movimiento como etiqueta suave con punto. */
function badge_estado(string $estado): string
{
    $map = [
        'PENDIENTE' => ['warn', 'Pendiente'], 'CONFIRMADO' => ['info', 'Confirmado'], 'COMPLETADO' => ['ok', 'Completado'],
        'CANCELADO' => ['muted', 'Cancelado'], 'ACTIVA' => ['ok', 'Activa'], 'ANULADA' => ['bad', 'Anulada'],
        'ENTRADA' => ['ok', 'Entrada'], 'SALIDA' => ['bad', 'Salida'], 'AJUSTE' => ['info', 'Ajuste'],
        'VENTA' => ['brand', 'Venta'], 'ANULACION' => ['muted', 'Anulación'],
    ];
    [$tono, $txt] = $map[$estado] ?? ['muted', ucfirst(strtolower($estado))];
    return '<span class="c-pill c-pill--' . $tono . '">' . e($txt) . '</span>';
}

/** Estado de stock de un producto. */
function stock_estado(int $stock, int $minimo): array
{
    if ($stock <= 0) return ['bad', 'Agotado'];
    if ($minimo > 0 && $stock <= $minimo) return ['warn', 'Por reponer'];
    return ['ok', 'Disponible'];
}

function stock_pill(int $stock, int $minimo, bool $conCantidad = true): string
{
    [$tono, $txt] = stock_estado($stock, $minimo);
    return '<span class="c-pill c-pill--' . $tono . '">' . e($txt) . ($conCantidad && $stock > 0 ? ' · ' . num($stock) : '') . '</span>';
}

/** Mensaje amigable cuando una lista no tiene datos. */
function empty_state(string $icono, string $titulo, string $texto = '', string $accion = ''): string
{
    return '<div class="c-empty"><div class="c-empty__icon"><i class="bi bi-' . e($icono) . '"></i></div>'
        . '<h3>' . e($titulo) . '</h3>' . ($texto ? '<p>' . e($texto) . '</p>' : '') . $accion . '</div>';
}

/** Iniciales para el avatar del usuario. */
function iniciales(string $nombre): string
{
    $p = preg_split('/\s+/', trim($nombre)) ?: [''];
    $i = mb_substr($p[0], 0, 1) . (isset($p[1]) ? mb_substr($p[1], 0, 1) : '');
    return mb_strtoupper($i);
}

/** Logo del cliente (empresa que usa Cristalia): subido en Empresa o el predeterminado. */
function logo_cliente(): ?string
{
    $l = ajuste('empresa_logo');
    if ($l !== '' && is_file(ROOT_PATH . '/' . $l)) return $l;
    $def = 'assets/img/cliente/logo-cliente.png';
    return is_file(ROOT_PATH . '/' . $def) ? $def : null;
}

function saludo(): string
{
    $h = (int)date('G');
    return $h < 12 ? 'Buenos días' : ($h < 19 ? 'Buenas tardes' : 'Buenas noches');
}

function fecha_larga(?int $t = null): string
{
    $t ??= time();
    $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return $dias[(int)date('w', $t)] . ', ' . (int)date('j', $t) . ' de ' . $meses[(int)date('n', $t)] . ' de ' . date('Y', $t);
}

/** Variación porcentual con flecha (para indicadores). */
function variacion(float $actual, float $anterior): string
{
    if ($anterior <= 0) return $actual > 0 ? '<span class="c-delta c-delta--up"><i class="bi bi-arrow-up-right"></i> nuevo</span>' : '';
    $p = ($actual - $anterior) / $anterior * 100;
    $cls = $p >= 0 ? 'up' : 'down';
    return '<span class="c-delta c-delta--' . $cls . '"><i class="bi bi-arrow-' . ($p >= 0 ? 'up-right' : 'down-right') . '"></i> '
        . ($p >= 0 ? '+' : '') . num($p, 1) . ' %</span>';
}

/** Botones de exportación (PDF / Excel) que conservan los filtros actuales. */
function botones_exportar(array $extra = []): string
{
    return '<div class="c-export">'
        . '<a class="btn btn-soft btn-sm" target="_blank" href="' . e(url_actual(array_merge($extra, ['formato' => 'pdf']))) . '"><i class="bi bi-filetype-pdf"></i> PDF</a>'
        . '<a class="btn btn-soft btn-sm" href="' . e(url_actual(array_merge($extra, ['formato' => 'xlsx']))) . '"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</a>'
        . '</div>';
}
