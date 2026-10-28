<?php
[$ventas, $nVentas, $montoVentas] = $ventas;
$acc = '';
if (can('clientes.gestionar')) $acc .= '<a href="' . url('clientes/editar', ['id' => $c['id']]) . '" class="btn btn-light"><i class="bi bi-pencil"></i> Editar</a>';
if (can('pedidos.gestionar') && $c['activo']) $acc .= '<a href="' . url('pedidos/crear', ['cliente_id' => $c['id']]) . '" class="btn btn-primary"><i class="bi bi-cart-plus"></i> Nuevo pedido</a>';
$ultima = $ventas[0]['fecha'] ?? null;
?>
<?= ui_header($c['nombre'], '<span class="c-code">' . e(Cliente::documento($c)) . '</span>' . ($c['activo'] ? '' : ' · <span class="c-pill c-pill--muted">Inactivo</span>'), $acc, url('clientes')) ?>

<div class="row g-3 c-stagger">
  <div class="col-lg-4">
    <div class="c-card c-card__body h-100">
      <div class="d-flex align-items-center gap-3 mb-4">
        <span class="c-avatar c-avatar--lg"><?= e(iniciales($c['nombre'])) ?></span>
        <div class="min-w-0"><div class="c-display fs-5 text-truncate"><?= e($c['nombre']) ?></div><div class="small text-muted">Cliente desde <?= fecha($c['creado_en'], false) ?></div></div>
      </div>
      <div class="row g-2 mb-4 text-center">
        <div class="col-6"><div class="p-3 rounded-4" style="background:var(--c-bruma)"><div class="c-display fs-4" data-count="<?= $nVentas ?>"><?= $nVentas ?></div><small class="text-muted">compras</small></div></div>
        <div class="col-6"><div class="p-3 rounded-4" style="background:var(--c-hielo)"><div class="c-display fs-5" style="color:var(--c-azul)"><?= money($montoVentas) ?></div><small class="text-muted">en total</small></div></div>
      </div>
      <dl class="c-dl">
        <dt><i class="bi bi-telephone me-1"></i>Teléfono</dt><dd><?= $c['telefono'] ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $c['telefono'])) . '">' . e($c['telefono']) . '</a>' : '—' ?></dd>
        <dt><i class="bi bi-envelope me-1"></i>Correo</dt><dd class="text-break"><?= e($c['email'] ?: '—') ?></dd>
        <dt><i class="bi bi-geo-alt me-1"></i>Dirección</dt><dd><?= e($c['direccion'] ?: '—') ?></dd>
        <dt><i class="bi bi-clock-history me-1"></i>Última compra</dt><dd><?= $ultima ? fecha($ultima, false) : '—' ?></dd>
      </dl>
    </div>
  </div>
  <div class="col-lg-8 d-flex flex-column gap-3">
    <div class="c-card">
      <div class="c-card__head"><h2 class="c-card__title">Pedidos</h2><span class="c-pill c-pill--muted is-count"><?= count($pedidos) ?></span></div>
      <?php if ($pedidos): ?>
      <div class="c-table-wrap mt-2"><table class="c-table">
        <thead><tr><th>Pedido</th><th>Fecha</th><th class="c-hide-xs">Sucursal</th><th>Estado</th><th class="num">Total</th></tr></thead><tbody>
        <?php foreach ($pedidos as $p): ?><tr class="clickable" data-href="<?= url('pedidos/ver', ['id' => $p['id']]) ?>">
          <td class="strong"><?= e($p['numero']) ?></td><td class="small"><?= fecha($p['fecha']) ?></td><td class="c-hide-xs"><?= e($p['sucursal']) ?></td><td><?= badge_estado($p['estado']) ?></td><td class="num"><?= money($p['total']) ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
      <?php else: ?><?= empty_state('clipboard', 'Sin pedidos todavía') ?><?php endif; ?>
    </div>
    <div class="c-card">
      <div class="c-card__head"><h2 class="c-card__title">Compras</h2><span class="c-pill c-pill--muted is-count"><?= $nVentas ?></span></div>
      <?php if ($ventas): ?>
      <div class="c-table-wrap mt-2"><table class="c-table">
        <thead><tr><th>Comprobante</th><th>Fecha</th><th class="c-hide-xs">Pago</th><th>Estado</th><th class="num">Total</th></tr></thead><tbody>
        <?php foreach ($ventas as $v): ?><tr class="clickable" data-href="<?= url('ventas/ver', ['id' => $v['id']]) ?>">
          <td class="strong"><?= e($v['numero']) ?></td><td class="small"><?= fecha($v['fecha']) ?></td><td class="c-hide-xs"><?= e($v['metodo_pago']) ?></td><td><?= badge_estado($v['estado']) ?></td><td class="num"><?= money($v['total']) ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
      <?php else: ?><?= empty_state('receipt', 'Sin compras todavía') ?><?php endif; ?>
    </div>
  </div>
</div>
