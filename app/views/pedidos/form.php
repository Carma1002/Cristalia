<?php $sucSel = old('sucursal_id', Auth::sucursalFija() ?? ($sucursales[0]['id'] ?? '')); ?>
<?= ui_header('Nuevo pedido', 'Elija el cliente, agregue los productos y listo. El sistema verifica que haya unidades.', '', url('pedidos')) ?>

<form method="post" action="<?= url('pedidos/guardar') ?>" id="formPedido" class="row g-3 c-stagger">
  <?= csrf_field() ?>
  <div class="col-lg-8 d-flex flex-column gap-3">
    <section class="c-card c-card__body">
      <h2 class="c-section-title"><span class="num-step">1</span> Cliente</h2>
      <input type="hidden" name="cliente_id" id="cliente_id" value="<?= e(old('cliente_id', $cliente['id'] ?? '')) ?>">
      <div id="clienteElegido" class="<?= $cliente ? '' : 'd-none' ?> d-flex align-items-center gap-3 p-3 rounded-4 mb-2" style="background:var(--c-hielo)">
        <span class="c-avatar c-avatar--lg" id="cliIni"><?= $cliente ? e(iniciales($cliente['nombre'])) : '' ?></span>
        <div class="flex-grow-1 min-w-0"><div class="fw-semibold text-truncate" id="cliNom"><?= $cliente ? e($cliente['nombre']) : '' ?></div><small class="text-muted" id="cliDoc"><?= $cliente ? e(Cliente::documento($cliente)) : '' ?></small></div>
        <button type="button" class="btn btn-light btn-sm" id="cambiarCliente">Cambiar</button>
      </div>
      <div id="buscadorCliente" class="<?= $cliente ? 'd-none' : '' ?>">
        <div class="position-relative">
          <div class="c-search"><i class="bi bi-person"></i><input class="form-control form-control-lg" id="buscarCliente" placeholder="Escriba el nombre o la cédula del cliente…" autocomplete="off"></div>
          <div class="list-group position-absolute w-100 autocomplete d-none" id="sugCliente"></div>
        </div>
        <?php if (can('clientes.gestionar')): ?><a class="small fw-semibold d-inline-block mt-2" href="<?= url('clientes/crear', ['volver' => 'pedido']) ?>"><i class="bi bi-person-plus"></i> ¿Cliente nuevo? Regístrelo aquí</a><?php endif; ?>
      </div>
      <?php if (count($sucursales) > 1): ?>
      <div class="mt-3" style="max-width:340px"><label class="form-label" for="sucursal_id">Sucursal que despacha</label>
        <select name="sucursal_id" id="sucursal_id" class="form-select" required>
          <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $sucSel) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?>
        </select></div>
      <?php else: ?>
        <input type="hidden" name="sucursal_id" id="sucursal_id" value="<?= e($sucursales[0]['id'] ?? '') ?>">
      <?php endif; ?>
    </section>

    <section class="c-card c-card__body">
      <h2 class="c-section-title"><span class="num-step">2</span> Productos</h2>
      <div class="position-relative mb-3">
        <div class="c-search"><i class="bi bi-search"></i><input class="form-control form-control-lg" id="buscarProducto" placeholder="Buscar por código, nombre o modelo del vehículo…" autocomplete="off"></div>
        <div class="list-group position-absolute w-100 autocomplete d-none" id="sugProducto"></div>
      </div>
      <div id="items" class="d-flex flex-column gap-2"></div>
      <div id="vacio"><?= empty_state('cart', 'El pedido está vacío', 'Busque un producto arriba y tóquelo para agregarlo.') ?></div>
    </section>
  </div>

  <div class="col-lg-4">
    <div class="c-card c-card__body c-sticky">
      <h2 class="c-section-title"><span class="num-step">3</span> Resumen</h2>
      <div class="c-totals mb-3">
        <div><span class="text-muted">Productos</span><b id="nItems">0</b></div>
        <div><span class="text-muted">Unidades</span><b id="nUnid">0</b></div>
        <div class="is-total"><span>Total</span><span id="total"><?= money(0) ?></span></div>
      </div>
      <label class="form-label" for="observaciones">Observaciones <small class="text-muted fw-normal">(opcional)</small></label>
      <textarea name="observaciones" id="observaciones" class="form-control mb-3" rows="2" maxlength="255" placeholder="Ej.: instalación a domicilio"><?= e(old('observaciones')) ?></textarea>
      <button class="btn btn-primary btn-lg w-100" id="btnGuardar"><i class="bi bi-check2"></i> Crear pedido</button>
      <p class="small text-muted mt-2 mb-0"><i class="bi bi-shield-check" style="color:var(--c-cian)"></i> Al guardar se vuelve a verificar la existencia y las unidades quedan apartadas.</p>
    </div>
  </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const items = document.getElementById('items'), suc = document.getElementById('sucursal_id');
  const fmt = Cristalia.fmtMoney, esc = Cristalia.esc;
  function recalcular() {
    let t = 0, u = 0;
    items.querySelectorAll('[data-id]').forEach(row => {
      const c = parseInt(row.querySelector('.cant').value || 0, 10), p = parseFloat(row.dataset.precio), max = parseInt(row.dataset.disp, 10);
      row.querySelector('.sub').textContent = fmt(c * p); t += c * p; u += c;
      const mal = c > max; row.style.borderColor = mal ? '#E5A39B' : ''; row.style.background = mal ? 'var(--c-bad-bg)' : '';
      row.querySelector('.aviso').innerHTML = mal ? '<i class="bi bi-exclamation-triangle"></i> Sólo hay ' + max + ' disponibles' : '';
    });
    document.getElementById('total').textContent = fmt(t);
    document.getElementById('nItems').textContent = items.children.length;
    document.getElementById('nUnid').textContent = u;
    document.getElementById('vacio').classList.toggle('d-none', items.children.length > 0);
  }
  async function agregar(p, cant = 1, silencioso = false) {
    let row = items.querySelector('[data-id="' + p.id + '"]');
    if (row) { const i = row.querySelector('.cant'); i.value = parseInt(i.value, 10) + cant; recalcular(); row.animate([{ background: 'var(--c-hielo)' }, { background: '#fff' }], 700); return; }
    if (p.disponible <= 0 && !silencioso && !(await Cristalia.confirmar({ title: 'Producto sin unidades', text: p.nombre + ' no tiene unidades disponibles en esta sucursal. ¿Agregarlo de todos modos?', ok: 'Agregar igual' }))) return;
    row = document.createElement('div');
    row.className = 'd-flex flex-wrap align-items-center gap-3 p-3 rounded-4 border reveal'; row.style.background = '#fff';
    row.dataset.id = p.id; row.dataset.precio = p.precio; row.dataset.disp = p.disponible;
    row.innerHTML = '<input type="hidden" name="producto_id[]" value="' + p.id + '">'
      + '<div class="flex-grow-1 min-w-0" style="flex-basis:220px"><span class="c-code">' + esc(p.codigo) + '</span><div class="fw-semibold">' + esc(p.nombre) + '</div>'
      + '<small class="text-muted">' + fmt(p.precio) + ' c/u · ' + p.disponible + ' disponibles</small><div class="aviso small fw-semibold" style="color:var(--c-bad)"></div></div>'
      + '<div class="c-qty"><button type="button" data-step="-" aria-label="Menos">−</button><input type="number" name="cantidad[]" class="cant" min="1" value="' + cant + '" required aria-label="Cantidad"><button type="button" data-step="+" aria-label="Más">+</button></div>'
      + '<div class="text-end" style="min-width:110px"><div class="sub c-num" style="font-size:1.05rem"></div></div>'
      + '<button type="button" class="c-icon-btn quitar" style="width:36px;height:36px" aria-label="Quitar"><i class="bi bi-trash3"></i></button>';
    items.appendChild(row); recalcular();
  }
  items.addEventListener('input', recalcular);
  items.addEventListener('click', e => { const q = e.target.closest('.quitar'); if (q) { q.closest('[data-id]').remove(); recalcular(); } });
  if (suc.tagName === 'SELECT') suc.addEventListener('change', async () => {
    if (items.children.length && await Cristalia.confirmar({ title: '¿Cambiar de sucursal?', text: 'La disponibilidad cambia según la sucursal, así que se vaciará la lista de productos.', ok: 'Sí, cambiar' })) { items.innerHTML = ''; recalcular(); }
  });
  Cristalia.autocompletarProducto(document.getElementById('buscarProducto'), document.getElementById('sugProducto'), () => suc.value, p => { agregar(p); document.getElementById('buscarProducto').value = ''; });

  const elegir = c => {
    document.getElementById('cliente_id').value = c.id;
    document.getElementById('cliNom').textContent = c.nombre; document.getElementById('cliDoc').textContent = c.documento;
    document.getElementById('cliIni').textContent = c.nombre.split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();
    document.getElementById('clienteElegido').classList.remove('d-none'); document.getElementById('buscadorCliente').classList.add('d-none');
    document.getElementById('buscarProducto').focus();
  };
  Cristalia.autocompletarCliente(document.getElementById('buscarCliente'), document.getElementById('sugCliente'), elegir);
  document.getElementById('cambiarCliente').addEventListener('click', () => {
    document.getElementById('clienteElegido').classList.add('d-none'); document.getElementById('buscadorCliente').classList.remove('d-none');
    const i = document.getElementById('buscarCliente'); i.value = ''; i.focus();
  });

  document.getElementById('formPedido').addEventListener('submit', e => {
    if (!document.getElementById('cliente_id').value) { e.preventDefault(); e.stopImmediatePropagation(); Cristalia.confirmar({ title: 'Falta el cliente', text: 'Busque y seleccione el cliente del pedido.', ok: 'Entendido' }); return; }
    if (!items.children.length) { e.preventDefault(); e.stopImmediatePropagation(); Cristalia.confirmar({ title: 'El pedido está vacío', text: 'Agregue al menos un producto.', ok: 'Entendido' }); }
  });
  <?= json_encode($prefill, JSON_UNESCAPED_UNICODE) ?>.forEach(x => agregar(x.p, x.cant, true));
  <?php if ($producto): ?>
  Cristalia.buscarProductos(<?= json_encode($producto['codigo']) ?>, suc.value).then(r => { const p = r.find(x => x.id === <?= (int)$producto['id'] ?>); if (p) agregar(p); });
  <?php endif; ?>
  recalcular();
});
</script>
