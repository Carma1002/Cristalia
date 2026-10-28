<?php
/**
 * Cristalia · Controlador frontal
 * Todas las peticiones pasan por aquí: index.php?r=modulo/accion
 */
require __DIR__ . '/_verificar.php';
require __DIR__ . '/app/bootstrap.php';

if (!APP_INSTALLED) {
    header('Location: install.php');
    exit;
}

$routes = require APP_PATH . '/routes.php';
$route  = (string)($_GET['r'] ?? 'dashboard');

// Direcciones de la versión anterior: se redirigen a su nueva ubicación
$alias = [
    'catalogo'             => ['productos', ['vista' => 'tarjetas']],
    'existencias'          => ['productos', ['vista' => 'lista']],
    'stock-bajo'           => ['productos', ['estado' => 'bajo']],
    'roles'                => ['usuarios', []],
    'roles/editar'         => ['usuarios', []],
    'roles/crear'          => ['usuarios', []],
    'sucursales'           => ['empresa', []],
    'sucursales/crear'     => ['empresa', []],
    'sucursales/editar'    => ['empresa', []],
    'configuracion'        => ['empresa', []],
    'reportes/productos'   => ['reportes', []],
    'reportes/rotacion'    => ['reportes/inventario', []],
    'reportes/movimientos' => ['movimientos', []],
];
if (isset($alias[$route]) && !is_post()) {
    $q = $_GET;
    unset($q['r']);
    redirect($alias[$route][0], array_merge($alias[$route][1], $q));
}

if (!isset($routes[$route])) {
    http_response_code(404);
    $route = Auth::check() ? 'dashboard' : 'login';
    flash('warning', 'La página solicitada no existe.');
}

[$controller, $method, $perm] = $routes[$route];

try {
    if ($perm !== 'public') {
        if (!Auth::check()) {
            if ($route !== 'dashboard') flash('info', 'Inicie sesión para continuar.');
            redirect('login');
        }
        if ($perm !== 'auth' && !permitido($perm)) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Acceso denegado']);
            exit;
        }
    }

    // Toda petición POST debe traer un token CSRF válido
    if (is_post()) csrf_check();

    (new $controller())->$method();
} catch (AppException $ex) {
    flash('danger', $ex->getMessage());
    if (is_post()) keep_old($_POST);
    back();
}
