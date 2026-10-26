<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= url('pedidos/ver', ['id' => $p['id']]) ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
  <h1 class="h4 mb-0">Registrar venta · pedido <?= e($p['numero']) ?></h1>
</div>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card"><div class="card-header"><?= e($p['cliente']) ?> · <?= e($p['cliente_doc']) ?> · <?= e($p['sucursal']) ?></div>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Producto</th><th class="text-end">Cant.</th><th class="text-end">P. unit.</th><th class="text-end">Subtotal</th></tr></thead>
        <tbody><?php foreach ($det as $d): ?>
          <tr><td><?= e($d['codigo']) ?> · <?= e($d['nombre']) ?></td><td class="text-end"><?= (int)$d['cantidad'] ?></td>
            <td class="text-end"><?= money($d['precio_unitario']) ?></td><td class="text-end"><?= money($d['subtotal']) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    </div>
  </div>
  <div class="col-lg-5">
    <form method="post" action="<?= url('ventas/guardar') ?>" class="card card-body" id="formVenta">
      <?= csrf_field() ?><input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
      <div class="mb-3"><label class="form-label">Método de pago *</label>
        <select name="metodo_pago" class="form-select" required><option value="">Seleccione…</option>
          <?php foreach (Venta::METODOS_PAGO as $m): ?><option <?= selected($m, old('metodo_pago')) ?>><?= e($m) ?></option><?php endforeach; ?></select></div>
      <div class="mb-3"><label class="form-label">Descuento (<?= e(ajuste('moneda', '$')) ?>)</label>
        <input type="number" name="descuento" id="descuento" step="0.01" min="0" max="<?= (float)$p['total'] ?>" class="form-control" value="<?= e(old('descuento', '0')) ?>"></div>
      <dl class="row mb-3">
        <dt class="col-6">Subtotal</dt><dd class="col-6 text-end mb-1"><?= money($p['total']) ?></dd>
        <dt class="col-6 fs-5">Total</dt><dd class="col-6 text-end fs-5 fw-bold mb-0" id="totalVenta"><?= money($p['total']) ?></dd>
      </dl>
      <div class="alert alert-warning small py-2">Al confirmar se descontarán las existencias de <strong><?= e($p['sucursal']) ?></strong> y el pedido pasará a COMPLETADO. Se emitirá un <strong>comprobante administrativo interno</strong> (no es factura fiscal).</div>
      <button class="btn btn-success" onclick="return confirm('¿Confirmar la venta?')"><i class="bi bi-check2-circle"></i> Confirmar venta</button>
    </form>
  </div>
</div>
<script>
document.getElementById('descuento').addEventListener('input', e => {
  const sub = <?= (float)$p['total'] ?>, d = Math.min(Math.max(parseFloat(e.target.value || 0), 0), sub);
  document.getElementById('totalVenta').textContent = <?= json_encode(ajuste('moneda', '$')) ?> + ' ' + (sub - d).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
});
</script>
