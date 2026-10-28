<?php
$acc = '<a href="' . url('ventas/comprobante', ['id' => $v['id']]) . '" target="_blank" class="btn btn-light"><i class="bi bi-printer"></i> Imprimir</a>'
     . '<a href="' . url('ventas/comprobante-pdf', ['id' => $v['id']]) . '" target="_blank" class="btn btn-light"><i class="bi bi-filetype-pdf"></i> PDF</a>';
if ($v['estado'] === 'ACTIVA' && can('ventas.anular')) $acc .= '<button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#mAnular"><i class="bi bi-x-octagon"></i> Anular venta</button>';
?>
<?= ui_header('Venta ' . $v['numero'], fecha($v['fecha']) . ' · ' . badge_estado($v['estado']), $acc, url('ventas')) ?>

<?php if ($v['estado'] === 'ANULADA'): ?><div class="alert alert-danger reveal"><i class="bi bi-x-octagon"></i><div><b>Venta anulada.</b> Motivo: <?= e($v['motivo_anulacion']) ?>. Las unidades regresaron al inventario.</div></div><?php endif; ?>

<div class="row g-3 c-stagger">
  <div class="col-lg-8">
    <div class="c-card">
      <div class="c-card__head"><h2 class="c-card__title">Detalle</h2><span class="c-pill c-pill--info is-count"><i class="bi bi-wallet2"></i> <?= e($v['metodo_pago']) ?></span></div>
      <div class="c-table-wrap mt-2"><table class="c-table">
        <thead><tr><th>Producto</th><th class="num">Cant.</th><th class="num c-hide-xs">Precio</th><th class="num">Subtotal</th></tr></thead>
        <tbody><?php foreach ($det as $d): ?>
          <tr><td><span class="c-code"><?= e($d['codigo']) ?></span><div><?= e($d['nombre']) ?></div></td><td class="num strong"><?= (int)$d['cantidad'] ?></td>
            <td class="num c-hide-xs"><?= money($d['precio_unitario']) ?></td><td class="num"><?= money($d['subtotal']) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <div class="c-card__body pt-2"><div class="c-totals ms-auto" style="max-width:320px">
        <div><span class="text-muted">Subtotal</span><span><?= money($v['subtotal']) ?></span></div>
        <div><span class="text-muted">Descuento</span><span>− <?= money($v['descuento']) ?></span></div>
        <div class="is-total"><span>Total</span><span><?= money($v['total']) ?></span></div>
      </div></div>
    </div>
    <p class="small text-muted mt-2"><i class="bi bi-info-circle"></i> Comprobante administrativo interno. No válido como factura fiscal.</p>
  </div>
  <div class="col-lg-4">
    <div class="c-card c-card__body">
      <div class="c-eyebrow mb-2">Cliente</div>
      <a href="<?= url('clientes/ver', ['id' => $v['cliente_id']]) ?>" class="d-flex align-items-center gap-3 mb-3 text-reset"><span class="c-avatar c-avatar--lg"><?= e(iniciales($v['cliente'])) ?></span>
        <div class="min-w-0"><div class="fw-semibold text-truncate"><?= e($v['cliente']) ?></div><small class="text-muted"><?= e($v['cliente_doc']) ?></small></div></a>
      <dl class="c-dl">
        <dt>Pedido</dt><dd><a href="<?= url('pedidos/ver', ['id' => $v['pedido_id']]) ?>"><?= e($v['pedido_numero']) ?></a></dd>
        <dt>Sucursal</dt><dd><?= e($v['sucursal']) ?></dd>
        <dt>Atendido por</dt><dd><?= e($v['usuario'] ?: '—') ?></dd>
        <dt>Fecha</dt><dd><?= fecha($v['fecha']) ?></dd>
      </dl>
    </div>
  </div>
</div>

<?php if ($v['estado'] === 'ACTIVA' && can('ventas.anular')): ?>
<div class="modal fade" id="mAnular" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
    <form method="post" action="<?= url('ventas/anular') ?>" class="modal-content">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $v['id'] ?>">
      <div class="modal-body pt-4">
        <div class="c-confirm-ico is-danger"><i class="bi bi-x-octagon"></i></div>
        <h2 class="modal-title mb-1">¿Anular la venta <?= e($v['numero']) ?>?</h2>
        <p class="text-muted">Las unidades vendidas regresarán al inventario de <?= e($v['sucursal']) ?> y el pedido quedará cancelado.</p>
        <label class="form-label" for="motivo">Motivo de la anulación</label>
        <input name="motivo" id="motivo" class="form-control" required maxlength="255" placeholder="Ej.: el cliente devolvió el producto">
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Volver</button><button class="btn btn-danger">Sí, anular</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
