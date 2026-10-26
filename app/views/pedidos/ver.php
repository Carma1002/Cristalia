<?php $abierto = in_array($p['estado'], ['PENDIENTE', 'CONFIRMADO'], true); ?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a href="<?= url('pedidos') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
  <h1 class="h4 mb-0 me-auto">Pedido <?= e($p['numero']) ?> <?= badge_estado($p['estado']) ?></h1>
  <?php if ($abierto && can('ventas.registrar')): ?>
    <a href="<?= url('ventas/registrar', ['pedido_id' => $p['id']]) ?>" class="btn btn-success btn-sm"><i class="bi bi-cash-coin"></i> Registrar venta</a>
  <?php endif; ?>
  <?php if ($p['venta_id']): ?>
    <a href="<?= url('ventas/ver', ['id' => $p['venta_id']]) ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-receipt"></i> Venta <?= e($p['venta_numero']) ?></a>
  <?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-body">
      <div class="row small">
        <div class="col-sm-6"><strong>Cliente:</strong> <?= e($p['cliente']) ?> (<?= e($p['cliente_doc']) ?>)<br><strong>Teléfono:</strong> <?= e($p['cliente_tel'] ?: '—') ?></div>
        <div class="col-sm-6"><strong>Sucursal:</strong> <?= e($p['sucursal']) ?><br><strong>Fecha:</strong> <?= fecha($p['fecha']) ?> · <strong>Por:</strong> <?= e($p['usuario'] ?: '—') ?></div>
        <?php if ($p['observaciones']): ?><div class="col-12 mt-2"><strong>Observaciones:</strong> <?= e($p['observaciones']) ?></div><?php endif; ?>
      </div>
    </div></div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0 align-middle">
      <thead><tr><th>Producto</th><th class="text-end">Cant.</th><th class="text-end">P. unit.</th><th class="text-end">Subtotal</th>
        <?php if ($abierto): ?><th class="text-end">Disp.</th><?php endif; ?></tr></thead>
      <tbody>
      <?php $faltan = false; foreach ($det as $d): $ok = !$abierto || $d['disponible'] >= $d['cantidad']; $faltan = $faltan || !$ok; ?>
        <tr class="<?= $ok ? '' : 'table-danger' ?>"><td><span class="small text-muted"><?= e($d['codigo']) ?></span><br><?= e($d['nombre']) ?></td>
          <td class="text-end"><?= (int)$d['cantidad'] ?></td><td class="text-end"><?= money($d['precio_unitario']) ?></td>
          <td class="text-end"><?= money($d['subtotal']) ?></td>
          <?php if ($abierto): ?><td class="text-end"><?= $ok ? '<i class="bi bi-check-circle text-success"></i>' : '<span class="text-danger fw-bold">' . (int)$d['disponible'] . '</span>' ?></td><?php endif; ?></tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end"><?= money($p['total']) ?></td><?php if ($abierto): ?><td></td><?php endif; ?></tr></tfoot>
    </table></div></div>
    <?php if ($abierto && $faltan): ?><div class="alert alert-danger mt-3 mb-0">Hay productos sin existencia suficiente. Registre una entrada de inventario o cancele el pedido.</div><?php endif; ?>
    <?php if ($abierto): ?><p class="small text-muted mt-2">Las unidades de este pedido están reservadas y no pueden venderse en otros pedidos hasta completarlo o cancelarlo.</p><?php endif; ?>
  </div>

  <div class="col-lg-4">
    <?php if ($transiciones && can('pedidos.gestionar')): ?>
    <div class="card mb-3"><div class="card-header">Cambiar estado</div><div class="card-body">
      <form method="post" action="<?= url('pedidos/estado') ?>">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
        <select name="estado" class="form-select form-select-sm mb-2" required>
          <?php foreach ($transiciones as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
        </select>
        <input name="nota" class="form-control form-control-sm mb-2" placeholder="Nota (obligatoria para cancelar)" maxlength="255">
        <button class="btn btn-sm btn-primary w-100">Actualizar</button>
      </form>
    </div></div>
    <?php endif; ?>
    <div class="card"><div class="card-header">Historial</div>
      <ul class="list-group list-group-flush small">
        <?php foreach ($hist as $h): ?>
          <li class="list-group-item"><div class="d-flex justify-content-between"><span><?= $h['estado_anterior'] ? e($h['estado_anterior']) . ' → ' : '' ?><strong><?= e($h['estado_nuevo']) ?></strong></span><span class="text-muted"><?= fecha($h['fecha']) ?></span></div>
            <div class="text-muted"><?= e($h['usuario'] ?: '—') ?><?= $h['nota'] ? ' · ' . e($h['nota']) : '' ?></div></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
