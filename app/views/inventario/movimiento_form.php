<?php $sucSel = old('sucursal_id', input('sucursal_id', Auth::sucursalFija() ?? ($sucursales[0]['id'] ?? ''))); $tipoSel = old('tipo', $tipo); ?>
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= $producto ? url('productos/ver', ['id' => $producto['id']]) : url('movimientos') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
  <h1 class="h4 mb-0">Registrar movimiento de inventario</h1>
</div>
<form method="post" action="<?= url('movimientos/guardar') ?>" class="card card-body" style="max-width: 760px">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Tipo de movimiento *</label>
    <div class="btn-group w-100 flex-wrap" role="group">
      <?php foreach (['ENTRADA' => ['success', 'Entrada (compra, devolución)'], 'SALIDA' => ['danger', 'Salida (daño, uso interno)'], 'AJUSTE' => ['primary', 'Ajuste (conteo físico)']] as $t => [$c, $txt]): ?>
        <input type="radio" class="btn-check" name="tipo" id="t<?= $t ?>" value="<?= $t ?>" <?= checked($tipoSel === $t) ?> required>
        <label class="btn btn-outline-<?= $c ?>" for="t<?= $t ?>"><?= $txt ?></label>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="mb-3 position-relative">
    <label class="form-label">Producto *</label>
    <input type="hidden" name="producto_id" id="producto_id" value="<?= e(old('producto_id', $producto['id'] ?? '')) ?>">
    <input class="form-control" id="buscarProducto" placeholder="Escriba código o nombre…" autocomplete="off"
           value="<?= $producto ? e($producto['codigo'] . ' · ' . $producto['nombre']) : '' ?>">
    <div class="list-group position-absolute w-100 shadow autocomplete d-none" id="sugerencias"></div>
  </div>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Sucursal *</label>
      <select name="sucursal_id" id="sucursal_id" class="form-select" required>
        <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $sucSel) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-6"><label class="form-label" id="lblCantidad">Cantidad *</label>
      <input type="number" name="cantidad" min="0" class="form-control" required value="<?= e(old('cantidad')) ?>">
      <div class="form-text" id="ayudaCantidad"></div></div>
  </div>
  <div class="alert alert-light border mt-3 mb-0 py-2 small" id="infoStock">Seleccione un producto para ver su existencia.</div>
  <div class="mt-3"><label class="form-label">Motivo / referencia *</label>
    <input name="motivo" class="form-control" required maxlength="255" value="<?= e(old('motivo')) ?>" placeholder="Ej.: Compra factura proveedor N° 1234"></div>
  <div class="d-flex flex-wrap gap-2 mt-3">
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Registrar</button>
    <button class="btn btn-outline-primary" name="otro" value="1">Registrar y cargar otro</button>
  </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ayuda = { ENTRADA: 'Unidades que ingresan.', SALIDA: 'Unidades que salen (no puede tomar unidades reservadas en pedidos).', AJUSTE: 'Existencia FINAL contada físicamente (el sistema calcula la diferencia).' };
  const lbl = { ENTRADA: 'Cantidad que entra *', SALIDA: 'Cantidad que sale *', AJUSTE: 'Existencia real contada *' };
  const sync = () => { const t = document.querySelector('input[name=tipo]:checked')?.value || 'ENTRADA';
    document.getElementById('ayudaCantidad').textContent = ayuda[t]; document.getElementById('lblCantidad').textContent = lbl[t]; };
  document.querySelectorAll('input[name=tipo]').forEach(r => r.addEventListener('change', sync)); sync();

  const info = document.getElementById('infoStock');
  const suc = document.getElementById('sucursal_id');
  const pid = document.getElementById('producto_id');
  async function actualizar() {
    if (!pid.value) return;
    const txt = document.getElementById('buscarProducto').value.split(' · ')[0];
    const r = await Cristalia.buscarProductos(txt, suc.value);
    const p = r.find(x => String(x.id) === String(pid.value));
    if (p) info.innerHTML = `Existencia en la sucursal: <b>${p.stock}</b> · reservado en pedidos: <b>${p.stock - p.disponible}</b> · libre: <b>${p.disponible}</b>`;
  }
  Cristalia.autocompletarProducto(document.getElementById('buscarProducto'), document.getElementById('sugerencias'),
    () => suc.value, p => { pid.value = p.id; actualizar(); });
  suc.addEventListener('change', actualizar);
  actualizar();
});
</script>
