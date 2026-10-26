/* Cristalia · utilidades de interfaz */
(function () {
  'use strict';

  // Filas de tabla clicables
  document.addEventListener('click', e => {
    const tr = e.target.closest('tr.clickable');
    if (tr && !e.target.closest('a,button,input,select,form')) window.location = tr.dataset.href;
  });

  const debounce = (fn, ms = 250) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  async function getJSON(url) {
    const r = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
    if (!r.ok) return [];
    try { return await r.json(); } catch { return []; }
  }

  function autocompletar(input, lista, buscar, render, elegir) {
    let items = [], idx = -1;
    const cerrar = () => { lista.classList.add('d-none'); lista.innerHTML = ''; idx = -1; };
    const pintar = () => {
      lista.innerHTML = items.length ? items.map((it, i) =>
        `<button type="button" class="list-group-item list-group-item-action ${i === idx ? 'active' : ''}" data-i="${i}">${render(it)}</button>`).join('')
        : '<div class="list-group-item small text-muted">Sin resultados</div>';
      lista.classList.remove('d-none');
    };
    input.addEventListener('input', debounce(async () => {
      const q = input.value.trim();
      if (q.length < 2) return cerrar();
      items = await buscar(q); idx = -1; pintar();
    }));
    input.addEventListener('keydown', e => {
      if (lista.classList.contains('d-none')) return;
      if (e.key === 'ArrowDown') { idx = Math.min(idx + 1, items.length - 1); pintar(); e.preventDefault(); }
      else if (e.key === 'ArrowUp') { idx = Math.max(idx - 1, 0); pintar(); e.preventDefault(); }
      else if (e.key === 'Enter' && idx >= 0) { e.preventDefault(); elegir(items[idx]); cerrar(); }
      else if (e.key === 'Escape') cerrar();
    });
    lista.addEventListener('mousedown', e => {
      const b = e.target.closest('[data-i]'); if (!b) return;
      e.preventDefault(); elegir(items[b.dataset.i]); cerrar();
    });
    input.addEventListener('blur', () => setTimeout(cerrar, 150));
  }

  window.Cristalia = {
    buscarProductos: (q, sucursalId) => getJSON(`index.php?r=api/productos&q=${encodeURIComponent(q)}&sucursal_id=${encodeURIComponent(sucursalId || '')}`),
    buscarClientes: q => getJSON(`index.php?r=clientes/buscar&q=${encodeURIComponent(q)}`),

    autocompletarProducto(input, lista, getSucursal, onElegir) {
      autocompletar(input, lista, q => this.buscarProductos(q, getSucursal()),
        p => `<div class="d-flex justify-content-between gap-2"><span><small class="text-muted">${esc(p.codigo)}</small> ${esc(p.nombre)}
               <br><small class="text-muted">${esc(p.vehiculo)}</small></span>
               <span class="text-end text-nowrap small">${Number(p.precio).toFixed(2)}<br>
               <span class="badge ${p.disponible > 0 ? 'bg-success' : 'bg-danger'}">disp. ${p.disponible}</span></span></div>`,
        p => { input.value = `${p.codigo} · ${p.nombre}`; onElegir(p); });
    },

    autocompletarCliente(input, lista, onElegir) {
      autocompletar(input, lista, q => this.buscarClientes(q),
        c => `${esc(c.nombre)} <small class="text-muted">${esc(c.documento)}</small>`,
        c => { input.value = `${c.nombre} · ${c.documento}`; onElegir(c); });
    }
  };
})();
