<?php
/**
 * Funciones de apoyo para vistas y controladores.
 */

/** Error de negocio con mensaje apto para mostrar al usuario. */
class AppException extends RuntimeException {}

function e(mixed $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function cfg(string $key, mixed $default = null): mixed
{
    return $GLOBALS['config'][$key] ?? $default;
}

/** Valor de la tabla `configuracion` (con caché por petición). */
function ajuste(string $clave, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        try {
            $cache = [];
            foreach (DB::all('SELECT clave, valor FROM configuracion') as $r) $cache[$r['clave']] = $r['valor'];
        } catch (Throwable) { $cache = []; }
    }
    return $cache[$clave] ?? $default;
}

function url(string $route = 'dashboard', array $params = []): string
{
    $q = array_merge(['r' => $route], $params);
    return 'index.php?' . http_build_query($q);
}

function redirect(string $route, array $params = []): never
{
    header('Location: ' . url($route, $params));
    exit;
}

function back(string $fallback = 'dashboard'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $u = $ref ? parse_url($ref) : [];
    $refHost = ($u['host'] ?? '') . (isset($u['port']) ? ':' . $u['port'] : '');
    if ($ref && strcasecmp($refHost, (string)($_SERVER['HTTP_HOST'] ?? '')) === 0) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

function flash(string $type, string $msg): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}

function flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/** Guarda los datos de un formulario para re-mostrarlos tras un error. */
function keep_old(array $data): void { $_SESSION['_old'] = $data; }

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function clear_old(): void { unset($_SESSION['_old']); }

// ---------------------------------------------------------------------
// Entrada
// ---------------------------------------------------------------------
function input(string $key, mixed $default = null): mixed
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function input_int(string $key, ?int $default = null): ?int
{
    $v = input($key);
    if ($v === null || $v === '') return $default;
    return filter_var($v, FILTER_VALIDATE_INT) !== false ? (int)$v : $default;
}

function input_dec(string $key, float $default = 0): float
{
    $v = str_replace(',', '.', (string)input($key, ''));
    return is_numeric($v) ? round((float)$v, 2) : $default;
}

function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

// ---------------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $t = $_POST['_csrf'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(419);
        throw new AppException('La sesión del formulario expiró. Recargue la página e intente de nuevo.');
    }
}

// ---------------------------------------------------------------------
// Formato
// ---------------------------------------------------------------------
function money(mixed $n): string
{
    return ajuste('moneda', '$') . ' ' . number_format((float)$n, 2, ',', '.');
}

function num(mixed $n, int $dec = 0): string
{
    return number_format((float)$n, $dec, ',', '.');
}

function fecha(?string $f, bool $hora = true): string
{
    if (!$f) return '—';
    $t = strtotime($f);
    return $t ? date($hora ? 'd/m/Y H:i' : 'd/m/Y', $t) : '—';
}

function can(string|array $perm): bool { return permitido($perm); }

function selected(mixed $a, mixed $b): string { return (string)$a === (string)$b ? 'selected' : ''; }
function checked(bool $c): string { return $c ? 'checked' : ''; }

/** Paginación simple: devuelve [limit, offset, page]. */
function paginar(int $porPagina = 20): array
{
    $page = max(1, input_int('p', 1) ?? 1);
    return [$porPagina, ($page - 1) * $porPagina, $page];
}

function paginacion_html(int $total, int $porPagina, int $page): string
{
    $pages = (int)ceil($total / max(1, $porPagina));
    if ($pages <= 1) return '';
    $q = $_GET;
    $h = '<nav><ul class="pagination pagination-sm flex-wrap">';
    for ($i = 1; $i <= $pages; $i++) {
        if ($pages > 12 && $i > 2 && $i < $pages - 1 && abs($i - $page) > 2) {
            if ($i === 3 || $i === $pages - 2) $h .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
            continue;
        }
        $q['p'] = $i;
        $h .= '<li class="page-item ' . ($i === $page ? 'active' : '') . '"><a class="page-link" href="index.php?' . e(http_build_query($q)) . '">' . $i . '</a></li>';
    }
    return $h . '</ul></nav>';
}

/** Construye la URL actual cambiando/añadiendo parámetros (p. ej. para exportar con los mismos filtros). */
function url_actual(array $cambios): string
{
    return 'index.php?' . http_build_query(array_merge($_GET, $cambios));
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}
