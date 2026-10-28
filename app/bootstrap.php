<?php
/**
 * Cristalia · Arranque de la aplicación
 * Carga configuración, sesión, autoloader y utilidades comunes.
 */
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);
define('APP_NAME', 'Cristalia');
define('APP_VERSION', '2.0.0');

mb_internal_encoding('UTF-8');

// ---------------------------------------------------------------------
// Configuración
// ---------------------------------------------------------------------
$configFile = APP_PATH . '/config.php';
define('APP_INSTALLED', is_file($configFile));
$GLOBALS['config'] = APP_INSTALLED ? require $configFile : [];

date_default_timezone_set($GLOBALS['config']['timezone'] ?? 'America/Caracas');

$debug = (bool)($GLOBALS['config']['debug'] ?? false);
ini_set('display_errors', $debug ? '1' : '0');
error_reporting(E_ALL);

// ---------------------------------------------------------------------
// Sesión segura
// ---------------------------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('CRISTALIA');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---------------------------------------------------------------------
// Autoloader simple (core, models, services, controllers)
// ---------------------------------------------------------------------
spl_autoload_register(function (string $class): void {
    foreach (['core', 'models', 'services', 'controllers'] as $dir) {
        $file = APP_PATH . "/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

require APP_PATH . '/core/helpers.php';
require APP_PATH . '/core/ui.php';

// Errores no controlados: página amigable (detalle sólo en modo debug)
set_exception_handler(function (Throwable $e) use ($debug): void {
    http_response_code(500);
    error_log('[Cristalia] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    $msg = $debug ? $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() : 'Ocurrió un error inesperado. Intente de nuevo.';
    echo '<!doctype html><meta charset="utf-8"><title>Error</title>'
       . '<div style="font-family:sans-serif;max-width:640px;margin:60px auto;padding:20px;border:1px solid #ddd;border-radius:8px">'
       . '<h2>Algo salió mal</h2><pre style="white-space:pre-wrap">' . htmlspecialchars($msg) . '</pre>'
       . '<a href="index.php">Volver al inicio</a></div>';
});
