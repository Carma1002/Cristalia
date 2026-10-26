<?php [$ventas, $nVentas, $montoVentas] = $ventas; ?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a href="<?= url('clientes') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
  <h1 class="h4 mb-0 me-auto"><?= e($c['nombre']) ?> <?= $c['activo'] ? '' : '<span class="badge bg-secondary">Inactivo</span>' ?></h1>
  <?php if (can('pedidos.gestionar') && $c['activo']): ?><a href="<?= url('pedidos/crear', ['cliente_id' => $c['id']]) ?>" class="btn btn-sm btn-success"><i class="bi bi-cart-plus"></i> Nuevo pedido</a><?php endif; ?>
  <?php if (can('clientes.gestionar')): ?><a href="<?= url('clientes/editar', ['id' => $c['id']]) ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Editar</a><?php endif; ?>
</div>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-body">
      <dl class="row small mb-0">
        <dt class="col-4">Documento</dt><dd class="col-8"><?= e(Cliente::documento($c)) ?></dd>
        <dt class="col-4">Teléfono</dt><dd class="col-8"><?= e($c['telefono'] ?: '—') ?></dd>
        <dt class="col-4">Correo</dt><dd class="col-8 text-break"><?= e($c['email'] ?: '—') ?></dd>
        <dt class="col-4">Dirección</dt><dd class="col-8"><?= e($c['direccion'] ?: '—') ?></dd>
        <dt class="col-4">Registrado</dt><dd class="col-8"><?= fecha($c['creado_en'], false) ?></dd>
        <dt class="col-4">Compras</dt><dd class="col-8 mb-0"><?= $nVentas ?> · <?= money($montoVentas) ?></dd>
      </dl>
    </div></div>
  </div>
  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-header">Pedidos</div><div class="table-responsive"><table class="table table-sm table-hover mb-0">
      <thead><tr><th>N°</th><th>Fecha</th><th>Sucursal</th><th>Estado</th><th class="text-end">Total</th></tr></thead><tbody>
      <?php foreach ($pedidos as $p): ?><tr class="clickable" data-href="<?= url('pedidos/ver', ['id' => $p['id']]) ?>">
        <td><?= e($p['numero']) ?></td><td><?= fecha($p['fecha']) ?></td><td><?= e($p['sucursal']) ?></td><td><?= badge_estado($p['estado']) ?></td><td class="text-end"><?= money($p['total']) ?></td></tr>
      <?php endforeach; if (!$pedidos): ?><tr><td colspan="5" class="text-muted">Sin pedidos.</td></tr><?php endif; ?>
      </tbody></table></div></div>
    <div class="card"><div class="card-header">Ventas</div><div class="table-responsive"><table class="table table-sm table-hover mb-0">
      <thead><tr><th>Comprobante</th><th>Fecha</th><th>Método</th><th>Estado</th><th class="text-end">Total</th></tr></thead><tbody>
      <?php foreach ($ventas as $v): ?><tr class="clickable" data-href="<?= url('ventas/ver', ['id' => $v['id']]) ?>">
        <td><?= e($v['numero']) ?></td><td><?= fecha($v['fecha']) ?></td><td><?= e($v['metodo_pago']) ?></td><td><?= badge_estado($v['estado']) ?></td><td class="text-end"><?= money($v['total']) ?></td></tr>
      <?php endforeach; if (!$ventas): ?><tr><td colspan="5" class="text-muted">Sin ventas.</td></tr><?php endif; ?>
      </tbody></table></div></div>
  </div>
</div>
