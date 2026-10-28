<?php
$u = Auth::user();
$secciones = nav_secciones();
$sucFija = Auth::sucursalFija();
$alertasStock = can('inventario.ver') ? Producto::stockBajo($sucFija, 6) : [];
$nAlertas = can('inventario.ver') ? InventarioService::contarStockBajo($sucFija) : 0;
$nPendientes = can('pedidos.ver') ? Pedido::contarAbiertos($sucFija) : 0;
$badges = ['comercio' => $nPendientes];
$logoCliente = logo_cliente();
$flashes = flashes();
$errores = array_filter($flashes, fn($f) => $f['type'] === 'danger');
$avisos = array_filter($flashes, fn($f) => $f['type'] !== 'danger');
$puedeBuscar = can(['inventario.ver', 'catalogo.ver']) || can(['clientes.ver']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0F536B">
  <title><?= e($title ?? '') ?> · <?= APP_NAME ?></title>
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
  <link href="assets/vendor/fonts/fonts.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/app.css?v=<?= APP_VERSION ?>" rel="stylesheet">
</head>
<body>
<div id="c-loader"></div>

<header class="c-topbar">
  <div class="c-topbar__inner">
    <a class="c-brand" href="<?= url('dashboard') ?>" aria-label="Cristalia, inicio">
      <img src="assets/img/cristalia-horizontal.svg" alt="Cristalia">
    </a>

    <nav class="c-nav" aria-label="Menú principal">
      <?php foreach ($secciones as $s): ?>
        <a class="c-nav__item<?= $s['activa'] ? ' is-active' : '' ?>" href="<?= $s['href'] ?>" <?= $s['activa'] ? 'aria-current="page"' : '' ?>>
          <i class="bi bi-<?= e($s['icon']) ?>"></i><span><?= e($s['label']) ?></span>
          <?php if (!empty($badges[$s['key']])): ?><span class="c-nav__badge"><?= (int)$badges[$s['key']] ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="c-actions">
      <?php if ($puedeBuscar): ?>
        <button class="c-icon-btn" type="button" data-bs-toggle="modal" data-bs-target="#cBuscar" aria-label="Buscar" title="Buscar (Ctrl + K)"><i class="bi bi-search"></i></button>
      <?php endif; ?>
      <?php if (can('inventario.ver') || can('pedidos.ver')): ?>
      <div class="dropdown">
        <button class="c-icon-btn" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Avisos">
          <i class="bi bi-bell"></i>
          <?php if ($nAlertas + $nPendientes > 0): ?><span class="c-dot"><?= min(99, $nAlertas + $nPendientes) ?></span><?php endif; ?>
        </button>
        <div class="dropdown-menu dropdown-menu-end c-alert-list">
          <div class="px-2 pt-1 pb-2 d-flex justify-content-between align-items-center">
            <strong class="c-display" style="font-size:1rem">Avisos</strong>
            <span class="c-pill c-pill--muted is-count"><?= $nAlertas + $nPendientes ?></span>
          </div>
          <?php if ($nPendientes && can('pedidos.ver')): ?>
            <a class="c-alert-item" href="<?= url('pedidos', ['estado' => 'abiertos']) ?>">
              <span class="c-alert-item__ico" style="background:var(--c-info-bg);color:var(--c-azul)"><i class="bi bi-clipboard-check"></i></span>
              <span><b><?= $nPendientes ?> pedido<?= $nPendientes === 1 ? '' : 's' ?> por completar</b><br><small class="text-muted">Esperando que se registre la venta</small></span>
            </a>
          <?php endif; ?>
          <?php foreach ($alertasStock as $a): ?>
            <a class="c-alert-item" href="<?= url('productos/ver', ['id' => $a['id']]) ?>">
              <span class="c-alert-item__ico" style="background:<?= $a['cantidad'] == 0 ? 'var(--c-bad-bg);color:var(--c-bad)' : 'var(--c-warn-bg);color:var(--c-warn)' ?>"><i class="bi bi-box-seam"></i></span>
              <span class="text-truncate"><b><?= e($a['nombre']) ?></b><br><small class="text-muted"><?= e($a['sucursal']) ?> · quedan <?= (int)$a['cantidad'] ?> (mínimo <?= (int)$a['stock_minimo'] ?>)</small></span>
            </a>
          <?php endforeach; ?>
          <?php if ($nAlertas > count($alertasStock)): ?>
            <a class="dropdown-item justify-content-center text-primary mt-1" href="<?= url('productos', ['estado' => 'bajo']) ?>">Ver los <?= $nAlertas ?> productos por reponer</a>
          <?php endif; ?>
          <?php if (!$nAlertas && !$nPendientes): ?>
            <div class="text-center text-muted small py-4"><i class="bi bi-check2-circle d-block fs-3 mb-1" style="color:var(--c-cian)"></i>Todo en orden por ahora</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <div class="dropdown">
        <button class="c-user" type="button" data-bs-toggle="dropdown" aria-label="Mi cuenta">
          <span class="c-avatar"><?= e(iniciales($u['nombre'])) ?></span>
          <span class="c-user__txt text-start pe-1"><span class="c-user__name d-block"><?= e($u['nombre']) ?></span><span class="c-user__role"><?= e($u['rol_nombre']) ?></span></span>
          <i class="bi bi-chevron-down small text-muted c-user__txt"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end" style="min-width:270px">
          <div class="c-menu-head">
            <span class="c-avatar c-avatar--lg"><?= e(iniciales($u['nombre'])) ?></span>
            <div class="min-w-0"><b class="d-block"><?= e($u['nombre']) ?></b><small class="text-muted"><?= e($u['rol_nombre']) ?> · <?= e($u['sucursal_nombre'] ?? 'Todas las sucursales') ?></small></div>
          </div>
          <div class="c-chip w-100 mb-2" style="border-radius:12px">
            <?php if ($logoCliente): ?><img src="<?= e($logoCliente) ?>" alt=""><?php endif; ?>
            <span class="text-truncate small"><?= e(ajuste('empresa_nombre')) ?></span>
          </div>
          <a class="dropdown-item" href="<?= url('perfil') ?>"><i class="bi bi-key"></i> Cambiar contraseña</a>
          <form method="post" action="<?= url('logout') ?>"><?= csrf_field() ?>
            <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right text-danger"></i> Cerrar sesión</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</header>

<main class="c-main">
  <?= ui_tabs() ?>
  <?php foreach ($errores as $f): ?>
    <div class="alert alert-danger alert-dismissible fade show reveal" role="alert">
      <i class="bi bi-exclamation-circle"></i><div><?= nl2br(e($f['msg'])) ?></div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  <?php endforeach; ?>
  <?= $content ?>
</main>

<nav class="c-bottomnav" aria-label="Menú">
  <?php foreach ($secciones as $s): ?>
    <a href="<?= $s['href'] ?>" class="<?= $s['activa'] ? 'is-active' : '' ?>">
      <i class="bi bi-<?= e($s['icon']) ?><?= $s['activa'] ? '-fill' : '' ?>"></i><span><?= e($s['key'] === 'admin' ? 'Admin.' : $s['label']) ?></span>
      <?php if (!empty($badges[$s['key']])): ?><span class="c-nav__badge"><?= (int)$badges[$s['key']] ?></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
</nav>

<div class="c-toasts" aria-live="polite">
  <?php foreach ($avisos as $f): ?>
    <div class="c-toast is-<?= e($f['type']) ?>" role="status">
      <i class="bi bi-<?= $f['type'] === 'warning' ? 'exclamation-triangle' : ($f['type'] === 'info' ? 'info-circle' : 'check-circle') ?>"></i>
      <div><?= nl2br(e($f['msg'])) ?></div>
      <button type="button" aria-label="Cerrar" data-close><i class="bi bi-x-lg" style="color:inherit;font-size:.8rem"></i></button>
    </div>
  <?php endforeach; ?>
</div>

<!-- Confirmación -->
<div class="modal fade" id="cConfirm" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
    <div class="modal-content">
      <div class="modal-body pt-4">
        <div class="c-confirm-ico"><i class="bi bi-question-lg"></i></div>
        <h2 class="modal-title mb-1" data-title>¿Confirmar?</h2>
        <p class="text-muted mb-0" data-text></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" data-ok>Confirmar</button>
      </div>
    </div>
  </div>
</div>

<?php if ($puedeBuscar): ?>
<!-- Búsqueda rápida -->
<div class="modal fade c-cmd" id="cBuscar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content overflow-hidden">
      <div class="position-relative">
        <i class="bi bi-search position-absolute" style="left:20px;top:50%;transform:translateY(-50%);color:var(--c-pizarra)"></i>
        <input class="form-control c-cmd__input" id="cBuscarInput" placeholder="Buscar producto, vehículo o cliente…" autocomplete="off">
      </div>
      <div class="c-cmd__results" id="cBuscarRes">
        <div class="text-center text-muted small py-4">Escriba al menos 2 letras. Ejemplo: <b>Corolla</b>, <b>parabrisas</b> o una cédula.</div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script>window.CRISTALIA = { moneda: <?= json_encode(ajuste('moneda', '$')) ?>, buscarProductos: <?= json_encode(can(['inventario.ver', 'catalogo.ver'])) ?>, buscarClientes: <?= json_encode(can('clientes.ver')) ?> };</script>
<script src="assets/js/app.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
