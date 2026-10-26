<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a href="<?= url('ventas') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
  <h1 class="h4 mb-0 me-auto">Venta <?= e($v['numero']) ?> <?= badge_estado($v['estado']) ?></h1>
  <a href="<?= url('ventas/comprobante', ['id' => $v['id']]) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> Imprimir</a>
  <a href="<?= url('ventas/comprobante-pdf', ['id' => $v['id']]) ?>" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-filetype-pdf"></i> PDF</a>
  <?php if ($v['estado'] === 'ACTIVA' && can('ventas.anular')): ?>
    <button class="btn btn-sm btn-danger" data-bs-toggle="collapse" data-bs-target="#anular"><i class="bi bi-x-octagon"></i> Anular</button>
  <?php endif; ?>
</div>
<?php if ($v['estado'] === 'ACTIVA' && can('ventas.anular')): ?>
<div class="collapse mb-3" id="anular"><form method="post" action="<?= url('ventas/anular') ?>" class="card card-body border-danger">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $v['id'] ?>">
  <label class="form-label">Motivo de la anulación *</label>
  <input name="motivo" class="form-control mb-2" required maxlength="255">
  <div class="small text-muted mb-2">Las unidades vendidas regresarán al inventario de <?= e($v['sucursal']) ?> y el pedido quedará CANCELADO.</div>
  <div><button class="btn btn-danger btn-sm" onclick="return confirm('¿Anular definitivamente esta venta?')">Confirmar anulación</button></div>
</form></div>
<?php endif; ?>
<?php if ($v['estado'] === 'ANULADA'): ?><div class="alert alert-danger">Venta anulada. Motivo: <?= e($v['motivo_anulacion']) ?></div><?php endif; ?>
<div class="row g-3">
  <div class="col-lg-4"><div class="card"><div class="card-body small">
    <dl class="row mb-0">
      <dt class="col-5">Fecha</dt><dd class="col-7"><?= fecha($v['fecha']) ?></dd>
      <dt class="col-5">Cliente</dt><dd class="col-7"><a href="<?= url('clientes/ver', ['id' => $v['cliente_id']]) ?>"><?= e($v['cliente']) ?></a><br><?= e($v['cliente_doc']) ?></dd>
      <dt class="col-5">Sucursal</dt><dd class="col-7"><?= e($v['sucursal']) ?></dd>
      <dt class="col-5">Pedido</dt><dd class="col-7"><a href="<?= url('pedidos/ver', ['id' => $v['pedido_id']]) ?>"><?= e($v['pedido_numero']) ?></a></dd>
      <dt class="col-5">Método</dt><dd class="col-7"><?= e($v['metodo_pago']) ?></dd>
      <dt class="col-5">Vendedor</dt><dd class="col-7 mb-0"><?= e($v['usuario'] ?: '—') ?></dd>
    </dl>
  </div></div></div>
  <div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
    <thead><tr><th>Producto</th><th class="text-end">Cant.</th><th class="text-end">P. unit.</th><th class="text-end">Subtotal</th></tr></thead>
    <tbody><?php foreach ($det as $d): ?>
      <tr><td><span class="small text-muted"><?= e($d['codigo']) ?></span> <?= e($d['nombre']) ?></td><td class="text-end"><?= (int)$d['cantidad'] ?></td>
        <td class="text-end"><?= money($d['precio_unitario']) ?></td><td class="text-end"><?= money($d['subtotal']) ?></td></tr>
    <?php endforeach; ?></tbody>
    <tfoot>
      <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end"><?= money($v['subtotal']) ?></td></tr>
      <tr><td colspan="3" class="text-end">Descuento</td><td class="text-end">− <?= money($v['descuento']) ?></td></tr>
      <tr class="fw-bold fs-6"><td colspan="3" class="text-end">Total</td><td class="text-end"><?= money($v['total']) ?></td></tr>
    </tfoot>
  </table></div></div>
  <p class="small text-muted mt-2">Comprobante administrativo interno. No válido como factura fiscal.</p></div>
</div>
