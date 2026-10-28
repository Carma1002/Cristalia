<?php
$sucSel = old('sucursal_id', input('sucursal_id', Auth::sucursalFija() ?? ($sucursales[0]['id'] ?? '')));
$tipos = [
    'ENTRADA' => ['box-arrow-in-down', 'Entrada', 'Llegó mercancía o una devolución', 'ok'],
    'SALIDA'  => ['box-arrow-up', 'Salida', 'Producto dañado, perdido o de uso interno', 'bad'],
    'AJUSTE'  => ['clipboard-check', 'Ajuste por conteo', 'Corregir con la cantidad real contada', ''],
];
?>
<?= ui_header('Registrar entrada o salida', 'Tres pasos: elija qué pasó, el producto y la cantidad.', '', $producto ? url('productos/ver', ['id' => $producto['id']]) : url('movimientos')) ?>

<form method="post" action="<?= url('movimientos/guardar') ?>" class="row g-3 c-stagger" id="formMov">
  <?= csrf_field() ?>
  <div class="col-lg-8 d-flex flex-column gap-3">
    <section class="c-card c-card__body">
      <h2 class="c-section-title"><span class="num-step">1</span> ¿Qué quiere registrar?</h2>
      <div class="c-tiles" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
        <?php foreach ($tipos as $k => [$ico, $t, $d, $tono]): ?>
          <div class="c-tile <?= $tono ? 'c-tile--' . $tono : '' ?>"><input type="radio" name="tipo" id="t<?= $k ?>" value="<?= $k ?>" <?= checked($tipo === $k) ?> required>
            <label for="t<?= $k ?>"><i class="bi bi-<?= $ico ?>"></i><?= $t ?><small><?= $d ?></small></label></div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="c-card c-card__body">
      <h2 class="c-section-title"><span class="num-step">2</span> Producto</h2>
      <div class="position-relative">
        <input type="hidden" name="producto_id" id="producto_id" value="<?= e(old('producto_id', $producto['id'] ?? '')) ?>">
        <div class="c-search"><i class="bi bi-search"></i>
          <input class="form-control form-control-lg" id="buscarProducto" placeholder="Escriba el código, el nombre o el modelo del vehículo…" autocomplete="off"
                 value="<?= $producto ? e($producto['codigo'] . ' · ' . $producto['nombre']) : '' ?>"></div>
        <div class="list-group position-absolute w-100 autocomplete d-none" id="sugerencias"></div>
      </div>
      <div class="alert alert-light mt-3 mb-0" id="infoStock"><i class="bi bi-info-circle"></i><div>Seleccione un producto para ver cuántas unidades hay.</div></div>
    </section>

    <section class="c-card c-card__body">
      <h2 class="c-section-title"><span class="num-step">3</span> Cantidad y motivo</h2>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="sucursal_id">Sucursal</label>
          <select name="sucursal_id" id="sucursal_id" class="form-select" required>
            <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $sucSel) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="col-md-6"><label class="form-label" id="lblCantidad">Cantidad</label>
          <div class="c-qty"><button type="button" data-step="-" aria-label="Menos">−</button><input type="number" name="cantidad" min="0" required value="<?= e(old('cantidad', '1')) ?>" aria-labelledby="lblCantidad"><button type="button" data-step="+" aria-label="Más">+</button></div>
          <div class="form-text" id="ayudaCantidad"></div></div>
        <div class="col-12"><label class="form-label" for="motivo">Motivo o referencia</label>
          <input name="motivo" id="motivo" class="form-control" required maxlength="255" value="<?= e(old('motivo')) ?>" placeholder="Ej.: Compra al proveedor, factura N° 1234">
          <div class="d-flex flex-wrap gap-1 mt-2" id="sugMotivo"></div></div>
      </div>
    </section>
  </div>

  <div class="col-lg-4">
    <div class="c-card c-card__body c-sticky">
      <h2 class="c-section-title">Resumen</h2>
      <dl class="c-dl mb-4" id="resumen">
        <dt>Tipo</dt><dd id="rTipo">—</dd>
        <dt>Existencia actual</dt><dd id="rActual">—</dd>
        <dt>Quedará en</dt><dd id="rFinal" class="c-num" style="font-size:1.2rem;color:var(--c-azul)">—</dd>
      </dl>
      <button class="btn btn-primary btn-lg w-100"><i class="bi bi-check2"></i> Registrar</button>
      <button class="btn btn-light w-100 mt-2" name="otro" value="1">Registrar y cargar otro</button>
    </div>
  </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ayuda = { ENTRADA: 'Unidades que entran a la sucursal.', SALIDA: 'Unidades que salen. No puede tomar las que están apartadas en pedidos.', AJUSTE: 'Escriba la cantidad REAL que contó. El sistema calcula la diferencia.' };
  const lbl = { ENTRADA: 'Cantidad que entra', SALIDA: 'Cantidad que sale', AJUSTE: 'Cantidad real contada' };
  const nombres = { ENTRADA: 'Entrada', SALIDA: 'Salida', AJUSTE: 'Ajuste por conteo' };
  const motivos = { ENTRADA: ['Compra al proveedor', 'Devolución de cliente', 'Traslado desde otra sucursal'], SALIDA: ['Producto dañado', 'Uso interno / instalación', 'Pérdida', 'Traslado a otra sucursal'], AJUSTE: ['Conteo físico de inventario'] };
  const form = document.getElementById('formMov'), suc = document.getElementById('sucursal_id'), pid = document.getElementById('producto_id');
  const cant = form.querySelector('[name=cantidad]'), info = document.getElementById('infoStock');
  let actual = null, libre = null;
  const tipo = () => form.querySelector('input[name=tipo]:checked')?.value || 'ENTRADA';
  function resumen() {
    const t = tipo(), c = parseInt(cant.value || '0', 10);
    document.getElementById('rTipo').textContent = nombres[t];
    document.getElementById('rActual').textContent = actual === null ? '—' : actual + ' unidades';
    let fin = actual === null ? null : (t === 'ENTRADA' ? actual + c : t === 'SALIDA' ? actual - c : c);
    const rf = document.getElementById('rFinal'); rf.textContent = fin === null ? '—' : fin + ' unidades';
    rf.style.color = fin !== null && fin < 0 ? 'var(--c-bad)' : 'var(--c-azul)';
  }
  function sync() {
    const t = tipo();
    document.getElementById('ayudaCantidad').textContent = ayuda[t]; document.getElementById('lblCantidad').textContent = lbl[t];
    document.getElementById('sugMotivo').innerHTML = motivos[t].map(m => '<button type="button" class="btn btn-light btn-sm" data-m="' + Cristalia.esc(m) + '">' + Cristalia.esc(m) + '</button>').join('');
    resumen();
  }
  document.getElementById('sugMotivo').addEventListener('click', e => { const b = e.target.closest('[data-m]'); if (b) document.getElementById('motivo').value = b.dataset.m; });
  form.querySelectorAll('input[name=tipo]').forEach(r => r.addEventListener('change', sync));
  cant.addEventListener('input', resumen);
  async function actualizar() {
    if (!pid.value) return;
    const txt = document.getElementById('buscarProducto').value.split(' · ')[0];
    const r = await Cristalia.buscarProductos(txt, suc.value);
    const p = r.find(x => String(x.id) === String(pid.value));
    if (!p) return;
    actual = p.stock; libre = p.disponible;
    info.className = 'alert ' + (p.stock > 0 ? 'alert-info' : 'alert-warning') + ' mt-3 mb-0';
    info.innerHTML = '<i class="bi bi-box-seam"></i><div>En <b>' + Cristalia.esc(suc.options[suc.selectedIndex].text) + '</b> hay <b>' + p.stock + '</b> unidades'
      + (p.stock - p.disponible > 0 ? ' (' + (p.stock - p.disponible) + ' apartadas en pedidos, ' + p.disponible + ' libres)' : '') + '.</div>';
    resumen();
  }
  Cristalia.autocompletarProducto(document.getElementById('buscarProducto'), document.getElementById('sugerencias'), () => suc.value, p => { pid.value = p.id; actualizar(); });
  suc.addEventListener('change', actualizar);
  sync(); actualizar();
});
</script>
