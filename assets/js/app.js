/* Cristalia · interacciones de la interfaz */
(function () {
  'use strict';
  const C = window.CRISTALIA || {};
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const debounce = (fn, ms = 220) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const fmtMoney = n => (C.moneda || '$') + ' ' + Number(n).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  /* ---------- Barra de carga al cambiar de página ---------- */
  const loader = $('#c-loader');
  const startLoad = () => loader && loader.classList.add('is-on');
  document.addEventListener('click', e => {
    const a = e.target.closest('a[href]');
    if (!a || e.defaultPrevented || e.ctrlKey || e.metaKey || e.shiftKey || a.target === '_blank' || a.hasAttribute('download')) return;
    const href = a.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript') || a.dataset.bsToggle) return;
    if (href.includes('formato=') || href.includes('descargar')) return; // descargas de archivos
    startLoad();
  });
  window.addEventListener('pageshow', () => {
    loader && loader.classList.remove('is-on');
    // Al volver con el botón "atrás" se desbloquean los formularios
    $$('form[data-sending]').forEach(f => delete f.dataset.sending);
    $$('.btn.is-loading').forEach(b => b.classList.remove('is-loading'));
  });

  /* ---------- Filas clicables ---------- */
  document.addEventListener('click', e => {
    const tr = e.target.closest('tr.clickable');
    if (tr && !e.target.closest('a,button,input,select,form,label')) { startLoad(); window.location = tr.dataset.href; }
  });

  /* ---------- Avisos emergentes ---------- */
  $$('.c-toast').forEach((t, i) => {
    const close = () => { t.classList.add('is-out'); setTimeout(() => t.remove(), 350); };
    t.style.animationDelay = (i * 120) + 'ms';
    setTimeout(close, 5200 + i * 300);
    t.querySelector('[data-close]')?.addEventListener('click', close);
  });

  /* ---------- Confirmaciones (en lugar de las ventanas del navegador) ---------- */
  const modalEl = $('#cConfirm');
  const modal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;
  function confirmar(opts) {
    return new Promise(resolve => {
      if (!modal) return resolve(window.confirm(opts.text || opts.title));
      $('[data-title]', modalEl).textContent = opts.title || '¿Confirmar?';
      $('[data-text]', modalEl).textContent = opts.text || '';
      const ok = $('[data-ok]', modalEl), ico = $('.c-confirm-ico', modalEl);
      ok.textContent = opts.ok || 'Confirmar';
      ok.className = 'btn ' + (opts.tone === 'danger' ? 'btn-danger' : 'btn-primary');
      ico.className = 'c-confirm-ico' + (opts.tone === 'danger' ? ' is-danger' : '');
      ico.innerHTML = '<i class="bi bi-' + (opts.tone === 'danger' ? 'exclamation-triangle' : 'question-lg') + '"></i>';
      let done = false;
      const onOk = () => { done = true; modal.hide(); resolve(true); };
      ok.onclick = onOk;
      modalEl.addEventListener('hidden.bs.modal', () => { if (!done) resolve(false); }, { once: true });
      modal.show();
    });
  }
  window.Cristalia = { confirmar, fmtMoney, esc };

  document.addEventListener('submit', async e => {
    if (e.defaultPrevented) return; // otra validación ya detuvo el envío
    const f = e.target;
    if (f.dataset.confirm && !f.dataset.confirmed) {
      e.preventDefault();
      const sub = e.submitter;
      const ok = await confirmar({ title: f.dataset.confirm, text: f.dataset.confirmText, ok: f.dataset.confirmOk, tone: f.dataset.confirmTone });
      if (!ok) return;
      f.dataset.confirmed = '1';
      if (sub && sub.name) { const h = document.createElement('input'); h.type = 'hidden'; h.name = sub.name; h.value = sub.value; f.appendChild(h); }
      f.requestSubmit ? f.requestSubmit() : f.submit();
      return;
    }
    // Evita doble envío (por ejemplo, registrar dos veces la misma venta)
    if (f.dataset.sending) { e.preventDefault(); return; }
    if (f.method.toLowerCase() === 'post' && !f.hasAttribute('data-no-lock')) {
      f.dataset.sending = '1';
      const b = e.submitter || f.querySelector('button[type=submit],button:not([type])');
      if (b) setTimeout(() => b.classList.add('is-loading'), 0);
      if (!f.target) startLoad();
    } else if (f.method.toLowerCase() === 'get') startLoad();
  });

  /* ---------- Cifras que cuentan hacia arriba ---------- */
  function countUp(el) {
    const end = parseFloat(el.dataset.count || '0'), dec = parseInt(el.dataset.decimals || '0', 10);
    const pre = el.dataset.prefix || '', suf = el.dataset.suffix || '';
    const fmt = v => pre + v.toLocaleString('es-VE', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + suf;
    if (reduceMotion || end === 0) { el.textContent = fmt(end); return; }
    const dur = 1100, t0 = performance.now();
    const step = t => { const p = Math.min(1, (t - t0) / dur), k = 1 - Math.pow(1 - p, 3); el.textContent = fmt(end * k); if (p < 1) requestAnimationFrame(step); };
    requestAnimationFrame(step);
  }
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(es => es.forEach(en => { if (en.isIntersecting) { countUp(en.target); io.unobserve(en.target); } }));
    $$('[data-count]').forEach(el => io.observe(el));
  } else $$('[data-count]').forEach(countUp);

  /* ---------- Mostrar / ocultar contraseña ---------- */
  $$('[data-toggle-pw]').forEach(b => b.addEventListener('click', () => {
    const i = b.parentElement.querySelector('input'); const show = i.type === 'password';
    i.type = show ? 'text' : 'password'; b.innerHTML = '<i class="bi bi-eye' + (show ? '-slash' : '') + '"></i>';
  }));

  /* ---------- Contador de cantidad (– / +) ---------- */
  document.addEventListener('click', e => {
    const b = e.target.closest('.c-qty button'); if (!b) return;
    const i = b.parentElement.querySelector('input'); const min = parseInt(i.min || '0', 10);
    i.value = Math.max(min, (parseInt(i.value || '0', 10) || 0) + (b.dataset.step === '-' ? -1 : 1));
    i.dispatchEvent(new Event('input', { bubbles: true }));
  });

  /* ---------- Autocompletado ---------- */
  async function getJSON(url) {
    try { const r = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } }); return r.ok ? await r.json() : []; }
    catch { return []; }
  }
  const buscarProductos = (q, suc) => getJSON('index.php?r=api/productos&q=' + encodeURIComponent(q) + '&sucursal_id=' + encodeURIComponent(suc || ''));
  const buscarClientes = q => getJSON('index.php?r=clientes/buscar&q=' + encodeURIComponent(q));

  function autocompletar(input, lista, buscar, render, elegir) {
    let items = [], idx = -1;
    const cerrar = () => { lista.classList.add('d-none'); lista.innerHTML = ''; idx = -1; };
    const pintar = () => {
      lista.innerHTML = items.length ? items.map((it, i) => '<button type="button" class="list-group-item list-group-item-action ' + (i === idx ? 'active' : '') + '" data-i="' + i + '">' + render(it) + '</button>').join('')
        : '<div class="list-group-item small text-muted">Sin resultados. Pruebe con otra palabra.</div>';
      lista.classList.remove('d-none');
    };
    input.addEventListener('input', debounce(async () => {
      const q = input.value.trim(); if (q.length < 2) return cerrar();
      items = await buscar(q); idx = -1; pintar();
    }));
    input.addEventListener('keydown', e => {
      if (lista.classList.contains('d-none')) return;
      if (e.key === 'ArrowDown') { idx = Math.min(idx + 1, items.length - 1); pintar(); e.preventDefault(); }
      else if (e.key === 'ArrowUp') { idx = Math.max(idx - 1, 0); pintar(); e.preventDefault(); }
      else if (e.key === 'Enter') { e.preventDefault(); if (items[idx >= 0 ? idx : 0]) { elegir(items[idx >= 0 ? idx : 0]); cerrar(); } }
      else if (e.key === 'Escape') cerrar();
    });
    lista.addEventListener('mousedown', e => { const b = e.target.closest('[data-i]'); if (!b) return; e.preventDefault(); elegir(items[b.dataset.i]); cerrar(); });
    input.addEventListener('blur', () => setTimeout(cerrar, 150));
  }
  const renderProducto = p => '<div class="d-flex justify-content-between gap-3 align-items-center"><span class="min-w-0"><span class="c-code">' + esc(p.codigo) + '</span><br><b style="font-weight:600">' + esc(p.nombre) + '</b>'
    + (p.vehiculo ? '<br><small class="text-muted">' + esc(p.vehiculo) + '</small>' : '') + '</span><span class="text-end text-nowrap"><b>' + fmtMoney(p.precio) + '</b><br>'
    + '<span class="c-pill c-pill--' + (p.disponible > 0 ? 'ok' : 'bad') + '">' + (p.disponible > 0 ? p.disponible + ' disp.' : 'Sin stock') + '</span></span></div>';

  Object.assign(window.Cristalia, {
    buscarProductos, buscarClientes,
    autocompletarProducto(input, lista, getSuc, onElegir) {
      autocompletar(input, lista, q => buscarProductos(q, getSuc()), renderProducto, p => { input.value = p.codigo + ' · ' + p.nombre; onElegir(p); });
    },
    autocompletarCliente(input, lista, onElegir) {
      autocompletar(input, lista, buscarClientes, c => '<b style="font-weight:600">' + esc(c.nombre) + '</b> <small class="text-muted">' + esc(c.documento) + '</small>', c => { input.value = c.nombre + ' · ' + c.documento; onElegir(c); });
    },
  });

  /* ---------- Búsqueda rápida (Ctrl + K) ---------- */
  const cmd = $('#cBuscar');
  if (cmd) {
    const inp = $('#cBuscarInput'), res = $('#cBuscarRes');
    let links = [], act = 0;
    cmd.addEventListener('shown.bs.modal', () => { inp.focus(); inp.select(); });
    document.addEventListener('keydown', e => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); bootstrap.Modal.getOrCreateInstance(cmd).show(); }
    });
    const pintarAct = () => links.forEach((l, i) => l.classList.toggle('is-active', i === act));
    inp.addEventListener('input', debounce(async () => {
      const q = inp.value.trim();
      if (q.length < 2) { res.innerHTML = '<div class="text-center text-muted small py-4">Escriba al menos 2 letras.</div>'; links = []; return; }
      const [ps, cs] = await Promise.all([C.buscarProductos ? buscarProductos(q, '') : [], C.buscarClientes ? buscarClientes(q) : []]);
      let h = '';
      if (ps.length) h += '<div class="c-cmd__group">Productos</div>' + ps.slice(0, 6).map(p => '<a class="c-cmd__item" href="index.php?r=productos/ver&id=' + p.id + '"><span class="c-list__ico"><i class="bi bi-box-seam"></i></span><span class="flex-grow-1 min-w-0"><b style="font-weight:600">' + esc(p.nombre) + '</b><br><small class="text-muted">' + esc(p.codigo) + (p.vehiculo ? ' · ' + esc(p.vehiculo) : '') + '</small></span><span class="text-end small"><b>' + fmtMoney(p.precio) + '</b><br><span class="text-muted">' + p.stock + ' en stock</span></span></a>').join('');
      if (cs.length) h += '<div class="c-cmd__group">Clientes</div>' + cs.slice(0, 5).map(c => '<a class="c-cmd__item" href="index.php?r=clientes/ver&id=' + c.id + '"><span class="c-list__ico"><i class="bi bi-person"></i></span><span><b style="font-weight:600">' + esc(c.nombre) + '</b><br><small class="text-muted">' + esc(c.documento) + (c.telefono ? ' · ' + esc(c.telefono) : '') + '</small></span></a>').join('');
      res.innerHTML = h || '<div class="text-center text-muted small py-4">No encontramos nada con "' + esc(q) + '".</div>';
      links = $$('.c-cmd__item', res); act = 0; pintarAct();
    }));
    inp.addEventListener('keydown', e => {
      if (!links.length) return;
      if (e.key === 'ArrowDown') { act = Math.min(act + 1, links.length - 1); pintarAct(); e.preventDefault(); }
      else if (e.key === 'ArrowUp') { act = Math.max(act - 1, 0); pintarAct(); e.preventDefault(); }
      else if (e.key === 'Enter') { e.preventDefault(); startLoad(); window.location = links[act].href; }
    });
  }

  /* ---------- Estilo de gráficos acorde a la marca ---------- */
  window.Cristalia.chartTheme = function () {
    if (!window.Chart) return;
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#5E6E77';
    Chart.defaults.borderColor = '#EDF2F4';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.tooltip.backgroundColor = '#092E3D';
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;
    Chart.defaults.plugins.tooltip.titleFont = { family: "'Poppins', sans-serif", weight: '500' };
    Chart.defaults.animation.duration = reduceMotion ? 0 : 900;
    Chart.defaults.animation.easing = 'easeOutQuart';
  };
  window.Cristalia.escala = ['#092E3D', '#0F536B', '#29809B', '#3FA7BF', '#8DC8D6', '#D7E9EF'];
})();
