<?php
$iconos = ['Efectivo' => 'cash-stack', 'Transferencia' => 'bank', 'Pago móvil' => 'phone', 'Punto de venta' => 'credit-card', 'Divisas' => 'currency-dollar', 'Zelle' => 'send', 'Mixto' => 'shuffle'];
$mon = ajuste('moneda', '$');
?>
<?= ui_header('Registrar venta', 'Pedido ' . e($p['numero']) . ' · ' . e($p['cliente']) . ' · ' . e($p['sucursal']), '', url('pedidos/ver', ['id' => $p['id']])) ?>

<form method="post" action="<?= url('ventas/guardar') ?>" class="row g-3 c-stagger" data-confirm="¿Confirmar la venta?" data-confirm-text="Se descontarán <?= array_sum(array_column($det, 'cantidad')) ?> unidades del inventario de <?= e($p['sucursal']) ?> y el pedido quedará completado." data-confirm-ok="Sí, registrar venta">
  <?= csrf_field() ?><input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
  <div class="col-lg-8 d-flex flex-column gap-3">
    <section class="c-card c-card__body">
      <h2 class="c-section-title"><span class="num-step">1</span> ¿Cómo pagó el cliente?</h2>
      <div class="c-tiles">
        <?php foreach (Venta::METODOS_PAGO as $i => $m): ?>
          <div class="c-tile"><input type="radio" name="metodo_pago" id="m<?= $i ?>" value="<?= e($m) ?>" <?= checked(old('metodo_pago') === $m) ?> required>
            <label for="m<?= $i ?>"><i class="bi bi-<?= $iconos[$m] ?? 'wallet2' ?>"></i><?= e($m) ?></label></div>
        <?php endforeach; ?>
      </div>
    </section>
    <section class="c-card">
      <div class="c-card__head"><h2 class="c-section-title mb-0"><span class="num-step">2</span> Revise los productos</h2></div>
      <div class="c-table-wrap mt-2"><table class="c-table">
        <thead><tr><th>Producto</th><th class="num">Cant.</th><th class="num c-hide-xs">Precio</th><th class="num">Subtotal</th></tr></thead>
        <tbody><?php foreach ($det as $d): ?>
          <tr><td><span class="c-code"><?= e($d['codigo']) ?></span><div><?= e($d['nombre']) ?></div></td><td class="num strong"><?= (int)$d['cantidad'] ?></td>
            <td class="num c-hide-xs"><?= money($d['precio_unitario']) ?></td><td class="num"><?= money($d['subtotal']) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    </section>
  </div>
  <div class="col-lg-4">
    <div class="c-card c-card__body c-sticky">
      <h2 class="c-section-title"><span class="num-step">3</span> Cobro</h2>
      <label class="form-label" for="descuento">Descuento <small class="text-muted fw-normal">(opcional)</small></label>
      <div class="input-group mb-3"><span class="input-group-text"><?= e($mon) ?></span>
        <input type="number" name="descuento" id="descuento" step="0.01" min="0" max="<?= (float)$p['total'] ?>" class="form-control" value="<?= e(old('descuento', '0')) ?>"></div>
      <div class="c-totals mb-3">
        <div><span class="text-muted">Subtotal</span><span><?= money($p['total']) ?></span></div>
        <div><span class="text-muted">Descuento</span><span id="desc">− <?= money(0) ?></span></div>
        <div class="is-total"><span>Total a cobrar</span><span id="totalVenta"><?= money($p['total']) ?></span></div>
      </div>
      <button class="btn btn-primary btn-lg w-100"><i class="bi bi-check2-circle"></i> Registrar venta</button>
      <p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle"></i> Se emitirá un <b>comprobante administrativo interno</b>. No es una factura fiscal.</p>
    </div>
  </div>
</form>
<script>
document.getElementById('descuento').addEventListener('input', e => {
  const sub = <?= (float)$p['total'] ?>, d = Math.min(Math.max(parseFloat(e.target.value || 0), 0), sub);
  document.getElementById('desc').textContent = '− ' + Cristalia.fmtMoney(d);
  document.getElementById('totalVenta').textContent = Cristalia.fmtMoney(sub - d);
});
</script>
