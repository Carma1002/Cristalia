<?php
$u = Auth::user();
$r = $_GET['r'] ?? 'dashboard';
$alertas = can('inventario.ver') ? InventarioService::contarStockBajo(Auth::sucursalFija()) : 0;
$abiertos = can('pedidos.ver') ? Pedido::contarAbiertos(Auth::sucursalFija()) : 0;

$menu = [
    'Principal' => [
        ['dashboard', 'speedometer2', 'Panel', 'auth'],
        ['catalogo', 'grid-3x3-gap', 'Catálogo', 'catalogo.ver'],
    ],
    'Inventario' => [
        ['productos', 'box-seam', 'Productos', 'inventario.ver'],
        ['existencias', 'building', 'Existencias', 'inventario.ver'],
        ['movimientos', 'arrow-left-right', 'Movimientos', 'inventario.ver'],
        ['stock-bajo', 'exclamation-triangle', 'Stock mínimo', 'inventario.ver', $alertas, 'danger'],
    ],
    'Comercial' => [
        ['clientes', 'people', 'Clientes', 'clientes.ver'],
        ['pedidos', 'clipboard-check', 'Pedidos', 'pedidos.ver', $abiertos, 'warning'],
        ['ventas', 'receipt', 'Ventas', 'ventas.ver'],
        ['reportes', 'bar-chart-line', 'Reportes', 'reportes.ver'],
    ],
    'Administración' => [
        ['sucursales', 'shop', 'Sucursales', 'admin.sucursales'],
        ['usuarios', 'person-gear', 'Usuarios', 'admin.usuarios'],
        ['roles', 'shield-lock', 'Roles y permisos', 'admin.roles'],
        ['bitacora', 'journal-text', 'Bitácora', 'admin.bitacora'],
        ['respaldos', 'database-down', 'Respaldos', 'admin.respaldos'],
        ['configuracion', 'gear', 'Configuración', 'admin.configuracion'],
    ],
];
$activo = fn(string $ruta) => $r === $ruta || str_starts_with($r, $ruta . '/') ? 'active' : '';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? '') ?> · <?= APP_NAME ?></title>
  <link rel="icon" href="assets/img/logo.svg" type="image/svg+xml">
  <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/app.css?v=<?= APP_VERSION ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-primary sticky-top shadow-sm">
  <div class="container-fluid">
    <button class="btn btn-link text-white d-lg-none p-0 me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Menú">
      <i class="bi bi-list fs-3"></i>
    </button>
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('dashboard') ?>">
      <img src="assets/img/logo.svg" alt="" width="28" height="28"> <?= APP_NAME ?>
    </a>
    <div class="dropdown ms-auto">
      <a class="text-white text-decoration-none dropdown-toggle small" href="#" data-bs-toggle="dropdown">
        <i class="bi bi-person-circle"></i> <span class="d-none d-sm-inline"><?= e($u['nombre']) ?></span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><span class="dropdown-item-text small text-muted"><?= e($u['rol_nombre']) ?><br><?= e($u['sucursal_nombre'] ?? 'Todas las sucursales') ?></span></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?= url('perfil') ?>"><i class="bi bi-key"></i> Cambiar contraseña</a></li>
        <li>
          <form method="post" action="<?= url('logout') ?>"><?= csrf_field() ?>
            <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</button>
          </form>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="d-flex">
  <aside class="offcanvas-lg offcanvas-start sidebar border-end bg-body" tabindex="-1" id="sidebar">
    <div class="offcanvas-header d-lg-none">
      <h5 class="offcanvas-title"><?= APP_NAME ?></h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar"></button>
    </div>
    <div class="offcanvas-body d-block p-2">
      <?php foreach ($menu as $grupo => $items):
        $visibles = array_filter($items, fn($i) => $i[3] === 'auth' || can($i[3]));
        if (!$visibles) continue; ?>
        <div class="text-uppercase text-muted small fw-semibold px-2 mt-3 mb-1"><?= e($grupo) ?></div>
        <ul class="nav nav-pills flex-column">
          <?php foreach ($visibles as $i): ?>
            <li class="nav-item">
              <a class="nav-link <?= $activo($i[0]) ?> d-flex align-items-center gap-2" href="<?= url($i[0]) ?>">
                <i class="bi bi-<?= $i[1] ?>"></i> <?= e($i[2]) ?>
                <?php if (!empty($i[4])): ?><span class="badge bg-<?= $i[5] ?> ms-auto"><?= (int)$i[4] ?></span><?php endif; ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endforeach; ?>
      <div class="small text-muted px-2 mt-4"><?= APP_NAME ?> v<?= APP_VERSION ?></div>
    </div>
  </aside>

  <main class="flex-grow-1 p-3 p-md-4 main-content">
    <?php foreach (flashes() as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
        <?= nl2br(e($f['msg'])) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endforeach; ?>
    <?= $content ?>
  </main>
</div>

<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
