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
        if ($perm !== 'auth' && !Auth::can($perm)) {
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
