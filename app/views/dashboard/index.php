<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0 me-auto">Panel de indicadores</h1>
  <?php if (!Auth::sucursalFija()): ?>
  <form class="d-flex gap-2" method="get">
    <input type="hidden" name="r" value="dashboard">
    <select name="sucursal_id" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="">Todas las sucursales</option>
      <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $suc) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?>
    </select>
  </form>
  <?php endif; ?>
</div>

<div class="row g-3 mb-3">
  <?php
  $cards = [
    ['Ventas de hoy', money($kpi['ventas_hoy']['m']), $kpi['ventas_hoy']['n'] . ' operaciones', 'cash-coin', 'success', 'ventas'],
    ['Ventas del mes', money($kpi['ventas_mes']['m']), $kpi['ventas_mes']['n'] . ' operaciones', 'calendar-check', 'primary', 'ventas'],
    ['Pedidos abiertos', num($kpi['pedidos_abiertos']), 'pendientes o confirmados', 'clipboard-check', 'warning', 'pedidos'],
    ['Alertas de stock', num($kpi['stock_bajo']), 'en o bajo el mínimo', 'exclamation-triangle', 'danger', 'stock-bajo'],
    ['Inventario al costo', money($kpi['valor_inv']), $kpi['productos'] . ' productos activos', 'box-seam', 'info', 'existencias'],
  ];
  foreach ($cards as $c): ?>
  <div class="col-6 col-md-4 col-xl">
    <a href="<?= url($c[5]) ?>" class="card h-100 text-decoration-none kpi border-0 border-start border-4 border-<?= $c[4] ?> shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between"><span class="text-muted small"><?= $c[0] ?></span><i class="bi bi-<?= $c[3] ?> text-<?= $c[4] ?>"></i></div>
        <div class="fs-5 fw-bold text-body"><?= $c[1] ?></div>
        <div class="small text-muted"><?= e($c[2]) ?></div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card h-100"><div class="card-header">Ventas mensuales (últimos 12 meses)</div>
      <div class="card-body"><canvas id="chartVentas" height="120"></canvas></div></div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100"><div class="card-header">Más vendidos (90 días)</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($top as $i => $t): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span class="text-truncate me-2"><?= $i + 1 ?>. <?= e($t['nombre']) ?></span>
            <span class="badge bg-primary rounded-pill"><?= num($t['unidades']) ?> u</span>
          </li>
        <?php endforeach; if (!$top): ?><li class="list-group-item text-muted">Sin ventas en el período.</li><?php endif; ?>
      </ul>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100"><div class="card-header d-flex justify-content-between">
      <span>Productos con poco stock</span><a href="<?= url('stock-bajo') ?>" class="small">Ver todos</a></div>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Producto</th><th>Sucursal</th><th class="text-end">Exist.</th><th class="text-end">Mín.</th></tr></thead>
        <tbody>
        <?php foreach ($stockBajo as $s): ?>
          <tr><td><?= e($s['codigo']) ?> · <?= e($s['nombre']) ?></td><td><?= e($s['sucursal']) ?></td>
            <td class="text-end fw-bold <?= $s['cantidad'] == 0 ? 'text-danger' : 'text-warning' ?>"><?= (int)$s['cantidad'] ?></td>
            <td class="text-end"><?= (int)$s['stock_minimo'] ?></td></tr>
        <?php endforeach; if (!$stockBajo): ?><tr><td colspan="4" class="text-muted">Todo en orden 👍</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100"><div class="card-header d-flex justify-content-between">
      <span>Mayor rotación (90 días)</span><a href="<?= url('reportes/rotacion') ?>" class="small">Ver reporte</a></div>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Producto</th><th class="text-end">Vendidas</th><th class="text-end">Rotación</th><th class="text-end">Días inv.</th></tr></thead>
        <tbody>
        <?php foreach ($rotacion as $x): ?>
          <tr><td><?= e($x['nombre']) ?></td><td class="text-end"><?= (int)$x['vendidas'] ?></td>
            <td class="text-end"><?= $x['rotacion'] === null ? '—' : num($x['rotacion'], 2) ?></td>
            <td class="text-end"><?= $x['dias_inventario'] ?? '—' ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </div>
  </div>

  <div class="col-12">
    <div class="card"><div class="card-header d-flex justify-content-between">
      <span>Últimos pedidos</span><a href="<?= url('pedidos') ?>" class="small">Ver todos</a></div>
      <div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead><tr><th>N°</th><th>Fecha</th><th>Cliente</th><th class="d-none d-md-table-cell">Sucursal</th><th>Estado</th><th class="text-end">Total</th></tr></thead>
        <tbody>
        <?php foreach ($ultimos as $p): ?>
          <tr class="clickable" data-href="<?= url('pedidos/ver', ['id' => $p['id']]) ?>">
            <td><?= e($p['numero']) ?></td><td><?= fecha($p['fecha']) ?></td><td><?= e($p['cliente']) ?></td>
            <td class="d-none d-md-table-cell"><?= e($p['sucursal']) ?></td><td><?= badge_estado($p['estado']) ?></td>
            <td class="text-end"><?= money($p['total']) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </div>
  </div>
</div>

<script src="assets/vendor/chartjs/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const datos = <?= json_encode($mensual, JSON_UNESCAPED_UNICODE) ?>;
  if (!window.Chart) return;
  new Chart(document.getElementById('chartVentas'), {
    type: 'bar',
    data: {
      labels: datos.map(d => d.etiqueta),
      datasets: [
        { label: 'Monto vendido', data: datos.map(d => d.monto), backgroundColor: 'rgba(13,110,253,.7)', yAxisID: 'y' },
        { label: 'N° de ventas', data: datos.map(d => d.n), type: 'line', borderColor: '#198754', backgroundColor: '#198754', tension: .3, yAxisID: 'y1' }
      ]
    },
    options: { responsive: true, interaction: { mode: 'index', intersect: false },
      scales: { y: { beginAtZero: true }, y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } } } }
  });
});
</script>
