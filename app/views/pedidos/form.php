<?php $sucSel = old('sucursal_id', Auth::sucursalFija() ?? ($sucursales[0]['id'] ?? '')); ?>
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= url('pedidos') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
  <h1 class="h4 mb-0">Nuevo pedido</h1>
</div>
<form method="post" action="<?= url('pedidos/guardar') ?>" id="formPedido">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card"><div class="card-body">
        <div class="mb-3 position-relative">
          <label class="form-label">Cliente *</label>
          <input type="hidden" name="cliente_id" id="cliente_id" value="<?= e(old('cliente_id', $cliente['id'] ?? '')) ?>">
          <input class="form-control" id="buscarCliente" placeholder="Nombre o cédula/RIF…" autocomplete="off"
                 value="<?= $cliente ? e($cliente['nombre'] . ' · ' . Cliente::documento($cliente)) : '' ?>">
          <div class="list-group position-absolute w-100 shadow autocomplete d-none" id="sugCliente"></div>
          <?php if (can('clientes.gestionar')): ?><a class="small" href="<?= url('clientes/crear', ['volver' => 'pedido']) ?>"><i class="bi bi-person-plus"></i> Registrar cliente nuevo</a><?php endif; ?>
        </div>
        <div class="mb-3"><label class="form-label">Sucursal *</label>
          <select name="sucursal_id" id="sucursal_id" class="form-select" required>
            <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $sucSel) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?>
          </select>
          <div class="form-text">La disponibilidad se verifica en esta sucursal.</div></div>
        <div class="mb-0"><label class="form-label">Observaciones</label>
          <textarea name="observaciones" class="form-control" rows="2" maxlength="255"><?= e(old('observaciones')) ?></textarea></div>
      </div></div>
    </div>
    <div class="col-lg-8">
      <div class="card"><div class="card-body">
        <div class="position-relative mb-3">
          <label class="form-label">Agregar producto</label>
          <input class="form-control" id="buscarProducto" placeholder="Escriba código, nombre o modelo de vehículo…" autocomplete="off">
          <div class="list-group position-absolute w-100 shadow autocomplete d-none" id="sugProducto"></div>
        </div>
        <div class="table-responsive"><table class="table table-sm align-middle" id="tablaItems">
          <thead><tr><th>Producto</th><th style="width:90px">Cant.</th><th class="text-end">Precio</th><th class="text-end">Subtotal</th><th></th></tr></thead>
          <tbody></tbody>
          <tfoot><tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold" id="total">0,00</td><td></td></tr></tfoot>
        </table></div>
        <div id="vacio" class="text-center text-muted small py-3">Aún no hay productos en el pedido.</div>
        <button class="btn btn-primary w-100 mt-2" id="btnGuardar"><i class="bi bi-check-lg"></i> Crear pedido</button>
        <p class="small text-muted mt-2 mb-0">Al guardar, el sistema verifica nuevamente las existencias y reserva las unidades.</p>
      </div></div>
    </div>
  </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const moneda = <?= json_encode(ajuste('moneda', '$')) ?>;
  const tbody = document.querySelector('#tablaItems tbody');
  const suc = document.getElementById('sucursal_id');
  const fmt = n => moneda + ' ' + Number(n).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  function recalcular() {
    let t = 0;
    tbody.querySelectorAll('tr').forEach(tr => {
      const c = parseInt(tr.querySelector('.cant').value || 0), p = parseFloat(tr.dataset.precio), max = parseInt(tr.dataset.disp);
      tr.querySelector('.sub').textContent = fmt(c * p); t += c * p;
      tr.classList.toggle('table-danger', c > max);
      tr.querySelector('.aviso').textContent = c > max ? `Solo hay ${max} disponibles` : '';
    });
    document.getElementById('total').textContent = fmt(t);
    document.getElementById('vacio').classList.toggle('d-none', tbody.children.length > 0);
  }
  function agregar(p, cant = 1, silencioso = false) {
    let tr = tbody.querySelector(`tr[data-id="${p.id}"]`);
    if (tr) { const i = tr.querySelector('.cant'); i.value = parseInt(i.value) + cant; recalcular(); return; }
    if (p.disponible <= 0 && !silencioso && !confirm(`${p.nombre} no tiene unidades disponibles en esta sucursal. ¿Agregar de todos modos?`)) return;
    tr = document.createElement('tr');
    tr.dataset.id = p.id; tr.dataset.precio = p.precio; tr.dataset.disp = p.disponible;
    tr.innerHTML = `<td><span class="small text-muted"></span><br><span class="nom"></span><div class="aviso small text-danger"></div>
        <input type="hidden" name="producto_id[]" value="${p.id}"></td>
      <td><input type="number" name="cantidad[]" class="form-control form-control-sm cant" min="1" value="${cant}" required></td>
      <td class="text-end text-nowrap">${fmt(p.precio)}<div class="small text-muted">disp. ${p.disponible}</div></td>
      <td class="text-end text-nowrap sub"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger quitar" aria-label="Quitar"><i class="bi bi-x"></i></button></td>`;
    tr.querySelector('.small').textContent = p.codigo;
    tr.querySelector('.nom').textContent = p.nombre;
    tbody.appendChild(tr); recalcular();
  }
  tbody.addEventListener('input', recalcular);
  tbody.addEventListener('click', e => { if (e.target.closest('.quitar')) { e.target.closest('tr').remove(); recalcular(); } });
  suc.addEventListener('change', () => { if (tbody.children.length && confirm('Al cambiar la sucursal se vaciará la lista de productos. ¿Continuar?')) { tbody.innerHTML = ''; recalcular(); } });

  Cristalia.autocompletarProducto(document.getElementById('buscarProducto'), document.getElementById('sugProducto'),
    () => suc.value, p => { agregar(p); document.getElementById('buscarProducto').value = ''; });
  Cristalia.autocompletarCliente(document.getElementById('buscarCliente'), document.getElementById('sugCliente'),
    c => document.getElementById('cliente_id').value = c.id);

  document.getElementById('formPedido').addEventListener('submit', e => {
    if (!document.getElementById('cliente_id').value) { e.preventDefault(); alert('Seleccione un cliente de la lista.'); return; }
    if (!tbody.children.length) { e.preventDefault(); alert('Agregue al menos un producto.'); }
  });

  <?= json_encode($prefill, JSON_UNESCAPED_UNICODE) ?>.forEach(x => agregar(x.p, x.cant, true));
  <?php if ($producto): ?>
  Cristalia.buscarProductos(<?= json_encode($producto['codigo']) ?>, suc.value).then(r => { const p = r.find(x => x.id === <?= (int)$producto['id'] ?>); if (p) agregar(p); });
  <?php endif; ?>
  recalcular();
});
</script>
