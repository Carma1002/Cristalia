<?php
$u = Auth::user();
$nombre = explode(' ', trim($u['nombre']))[0];
$mon = ajuste('moneda', '$') . ' ';
$maxSem = max(1, max($semana));
$nAbiertos = array_sum($abiertos);
$pctConf = $nAbiertos ? round($abiertos['CONFIRMADO'] / $nAbiertos * 100) : 0;
$logo = logo_cliente();
$acciones = array_filter([
    can('pedidos.gestionar') ? ['pedidos/crear', 'cart-plus', 'Nuevo pedido', 'Vender a un cliente'] : null,
    can('inventario.movimientos') ? ['movimientos/nuevo', 'box-arrow-in-down', 'Registrar entrada', 'Llegó mercancía'] : null,
    can('clientes.gestionar') ? ['clientes/crear', 'person-plus', 'Nuevo cliente', 'Registrar sus datos'] : null,
    can(['inventario.ver', 'catalogo.ver']) ? ['productos', 'search', 'Buscar producto', 'Precio y disponibilidad'] : null,
]);
?>
<section class="c-hello reveal">
  <div>
    <h1><?= saludo() ?>, <strong><?= e($nombre) ?></strong></h1>
    <p>Esto es lo que está pasando hoy en <?= e(rtrim(ajuste('empresa_nombre'), '.')) ?>.</p>
  </div>
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <span class="c-chip"><i class="bi bi-calendar3" style="color:var(--c-cian)"></i> <?= e(ucfirst(fecha_larga())) ?></span>
    <?php if (!Auth::sucursalFija() && count($sucursales) > 1): ?>
      <form method="get" class="m-0">
        <input type="hidden" name="r" value="dashboard">
        <select name="sucursal_id" class="form-select form-select-sm" style="border-radius:999px;min-width:190px" onchange="this.form.submit()" aria-label="Sucursal">
          <option value="">Todas las sucursales</option>
          <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $suc) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?>
        </select>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php if ($acciones): ?>
<div class="c-quick mb-4 c-stagger">
  <?php foreach ($acciones as [$r, $ico, $t, $sub]): ?>
    <a href="<?= url($r) ?>"><span class="c-quick__ico"><i class="bi bi-<?= $ico ?>"></i></span><span><?= e($t) ?><small><?= e($sub) ?></small></span></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-3 mb-3 c-stagger">
  <div class="col-6 col-xl-3">
    <a href="<?= can('ventas.ver') ? url('ventas', ['desde' => date('Y-m-d'), 'hasta' => date('Y-m-d')]) : '#' ?>" class="c-card c-card--link c-kpi">
      <div class="c-kpi__top"><span class="c-kpi__label">Ventas de hoy</span><span class="c-card-arrow"><i class="bi bi-arrow-up-right"></i></span></div>
      <div class="c-kpi__row">
        <div><div class="c-kpi__value" data-count="<?= (float)$ventasHoy['m'] ?>" data-decimals="2" data-prefix="<?= e($mon) ?>"><?= money($ventasHoy['m']) ?></div>
          <div class="c-kpi__foot"><?= (int)$ventasHoy['n'] ?> venta<?= (int)$ventasHoy['n'] === 1 ? '' : 's' ?> registradas</div></div>
        <div class="c-spark c-hide-xs" title="Últimos 7 días">
          <?php foreach ($semana as $i => $v): ?><span class="<?= $i === 6 ? 'is-hi' : '' ?>" style="height:<?= max(8, round($v / $maxSem * 100)) ?>%;animation-delay:<?= $i * 60 ?>ms"></span><?php endforeach; ?>
        </div>
      </div>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <a href="<?= can('reportes.ver') ? url('reportes') : '#' ?>" class="c-card c-card--link c-kpi">
      <div class="c-kpi__top"><span class="c-kpi__label">Ventas del mes</span><span class="c-card-arrow"><i class="bi bi-arrow-up-right"></i></span></div>
      <div class="c-kpi__row">
        <div><div class="c-kpi__value" data-count="<?= (float)$ventasMes['m'] ?>" data-decimals="2" data-prefix="<?= e($mon) ?>"><?= money($ventasMes['m']) ?></div>
          <div class="c-kpi__foot"><?= variacion((float)$ventasMes['m'], $ventasAnt) ?> <span>vs. mismo período del mes pasado</span></div></div>
      </div>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <a href="<?= can('pedidos.ver') ? url('pedidos', ['estado' => 'abiertos']) : '#' ?>" class="c-card c-card--link c-kpi">
      <div class="c-kpi__top"><span class="c-kpi__label">Pedidos por completar</span><span class="c-card-arrow"><i class="bi bi-arrow-up-right"></i></span></div>
      <div class="c-kpi__row">
        <div><div class="c-kpi__value" data-count="<?= $nAbiertos ?>"><?= $nAbiertos ?></div>
          <div class="c-kpi__foot"><?= $abiertos['PENDIENTE'] ?> pendientes · <?= $abiertos['CONFIRMADO'] ?> confirmados</div></div>
        <div class="c-ring c-hide-xs" style="--p:<?= $pctConf ?>" title="Confirmados"><b><?= $pctConf ?>%</b></div>
      </div>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <a href="<?= can('inventario.ver') ? url('productos', ['estado' => 'bajo']) : '#' ?>" class="c-card c-card--link c-kpi">
      <div class="c-kpi__top"><span class="c-kpi__label">Por reponer</span><span class="c-card-arrow"><i class="bi bi-arrow-up-right"></i></span></div>
      <div class="c-kpi__row">
        <div><div class="c-kpi__value" data-count="<?= $nBajo ?>"><?= $nBajo ?></div>
          <div class="c-kpi__foot"><?= $agotados ?> agotados · <?= $nBajo - $agotados ?> en el mínimo</div></div>
        <div class="c-dots c-hide-xs" title="Rojo: agotado · Ámbar: en el mínimo">
          <?php for ($i = 0; $i < 18; $i++): $cls = $i < min(18, $agotados) ? 'is-bad' : ($i < min(18, $nBajo) ? 'is-warn' : ''); ?><i class="<?= $cls ?>" style="animation-delay:<?= $i * 25 ?>ms"></i><?php endfor; ?>
        </div>
      </div>
    </a>
  </div>
</div>

<div class="row g-3 mb-3 c-stagger">
  <div class="col-lg-8">
    <div class="c-card h-100">
      <div class="c-card__head">
        <div><h2 class="c-card__title">Ventas mensuales</h2><p class="c-card__sub">Monto vendido en los últimos 12 meses</p></div>
        <?php if (can('reportes.ver')): ?><a href="<?= url('reportes', ['agrupar' => 'mes', 'desde' => $mensual[0]['mes'] . '-01']) ?>" class="c-card-arrow" aria-label="Ver reporte"><i class="bi bi-arrow-up-right"></i></a><?php endif; ?>
      </div>
      <div class="c-card__body"><div style="height:280px"><canvas id="chartVentas" aria-label="Gráfico de ventas mensuales"></canvas></div></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="c-card h-100">
      <div class="c-card__head"><div><h2 class="c-card__title">Ventas por sucursal</h2><p class="c-card__sub">Este mes</p></div></div>
      <div class="c-card__body">
        <?php $totSuc = array_sum(array_column($porSucursal, 'monto')); ?>
        <div style="height:170px" class="mb-3"><canvas id="chartSucursal" aria-label="Ventas por sucursal"></canvas></div>
        <?php foreach ($porSucursal as $i => $s): $pct = $totSuc > 0 ? $s['monto'] / $totSuc * 100 : 0; ?>
          <div class="d-flex align-items-center gap-2 mb-2 small">
            <span style="width:10px;height:10px;border-radius:3px;background:<?= ['#0F536B', '#3FA7BF', '#8DC8D6', '#092E3D', '#29809B', '#D7E9EF'][$i % 6] ?>"></span>
            <span class="flex-grow-1 text-truncate"><?= e($s['nombre']) ?></span>
            <span class="text-muted"><?= num($pct, 0) ?> %</span>
            <b class="c-num" style="min-width:90px;text-align:right"><?= money($s['monto']) ?></b>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 c-stagger">
  <?php if (can('inventario.ver')): ?>
  <div class="col-lg-4">
    <div class="c-card h-100">
      <div class="c-card__head"><div><h2 class="c-card__title">Productos por reponer</h2><p class="c-card__sub">En o por debajo del stock mínimo</p></div>
        <a href="<?= url('productos', ['estado' => 'bajo']) ?>" class="c-card-arrow" aria-label="Ver todos"><i class="bi bi-arrow-up-right"></i></a></div>
      <?php if ($bajo): ?>
      <ul class="c-list mt-2">
        <?php foreach ($bajo as $b): ?>
          <li><a href="<?= url('productos/ver', ['id' => $b['id']]) ?>">
            <span class="c-list__ico" style="<?= (int)$b['cantidad'] === 0 ? 'background:var(--c-bad-bg);color:var(--c-bad)' : 'background:var(--c-warn-bg);color:var(--c-warn)' ?>"><?= (int)$b['cantidad'] ?></span>
            <span class="c-list__main"><span class="c-list__title d-block"><?= e($b['nombre']) ?></span><span class="c-list__sub d-block"><?= e($b['sucursal']) ?> · mínimo <?= (int)$b['stock_minimo'] ?></span></span>
            <?= (int)$b['cantidad'] === 0 ? '<span class="c-pill c-pill--bad">Agotado</span>' : '<span class="c-pill c-pill--warn">Bajo</span>' ?>
          </a></li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?>
        <?= empty_state('check2-circle', 'Todo en orden', 'Ningún producto está por debajo de su mínimo.') ?>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <div class="col-lg-4">
    <div class="c-card h-100">
      <div class="c-card__head"><div><h2 class="c-card__title">Más vendidos</h2><p class="c-card__sub">Últimos 90 días</p></div>
        <?php if (can('reportes.ver')): ?><a href="<?= url('reportes', ['desde' => date('Y-m-d', strtotime('-90 days'))]) ?>#productos" class="c-card-arrow" aria-label="Ver reporte"><i class="bi bi-arrow-up-right"></i></a><?php endif; ?></div>
      <div class="c-card__body">
        <?php $maxTop = $top ? max(array_column($top, 'unidades')) : 1; ?>
        <?php foreach ($top as $i => $t): ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between small mb-1"><span class="text-truncate pe-2"><b class="me-1" style="color:var(--c-cian-texto)"><?= $i + 1 ?></b> <?= e($t['nombre']) ?></span><b class="text-nowrap"><?= num($t['unidades']) ?> u</b></div>
            <div class="c-bar"><span class="<?= $i === 0 ? 'is-cian' : '' ?>" style="width:<?= round($t['unidades'] / $maxTop * 100) ?>%;animation-delay:<?= $i * 90 ?>ms"></span></div>
          </div>
        <?php endforeach; ?>
        <?php if (!$top): ?><?= empty_state('bar-chart', 'Aún no hay ventas', 'Cuando se registren ventas verá aquí los productos más vendidos.') ?><?php endif; ?>
      </div>
    </div>
  </div>
  <?php if (can('pedidos.ver')): ?>
  <div class="col-lg-4">
    <div class="c-card h-100">
      <div class="c-card__head"><div><h2 class="c-card__title">Pedidos recientes</h2><p class="c-card__sub">Los últimos registrados</p></div>
        <a href="<?= url('pedidos') ?>" class="c-card-arrow" aria-label="Ver todos"><i class="bi bi-arrow-up-right"></i></a></div>
      <?php if ($ultimos): ?>
      <ul class="c-list mt-2">
        <?php foreach ($ultimos as $p): ?>
          <li><a href="<?= url('pedidos/ver', ['id' => $p['id']]) ?>">
            <span class="c-avatar" style="background:var(--c-hielo);color:var(--c-azul)"><?= e(iniciales($p['cliente'])) ?></span>
            <span class="c-list__main"><span class="c-list__title d-block"><?= e($p['cliente']) ?></span><span class="c-list__sub d-block"><?= e($p['numero']) ?> · <?= fecha($p['fecha']) ?></span></span>
            <span class="c-list__end"><b class="c-num d-block"><?= money($p['total']) ?></b><?= badge_estado($p['estado']) ?></span>
          </a></li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><?= empty_state('clipboard', 'Sin pedidos todavía') ?><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script src="assets/vendor/chartjs/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (!window.Chart) return;
  Cristalia.chartTheme();
  const datos = <?= json_encode($mensual, JSON_UNESCAPED_UNICODE) ?>;
  const ult = datos.length - 1;
  new Chart(document.getElementById('chartVentas'), {
    type: 'bar',
    data: {
      labels: datos.map(d => d.etiqueta),
      datasets: [
        { type: 'line', label: 'N° de ventas', data: datos.map(d => d.n), borderColor: '#3FA7BF', backgroundColor: '#3FA7BF', borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#fff', pointBorderWidth: 2, tension: .35, yAxisID: 'y1' },
        { label: 'Monto vendido', data: datos.map(d => d.monto), backgroundColor: datos.map((d, i) => i === ult ? '#0F536B' : '#D7E9EF'), hoverBackgroundColor: datos.map((d, i) => i === ult ? '#092E3D' : '#8DC8D6'), borderRadius: 8, borderSkipped: false, maxBarThickness: 38, yAxisID: 'y' },
      ]
    },
    options: {
      maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
      plugins: { legend: { position: 'top', align: 'end' }, tooltip: { callbacks: { label: c => c.dataset.yAxisID === 'y' ? ' ' + Cristalia.fmtMoney(c.parsed.y) : ' ' + c.parsed.y + ' ventas' } } },
      scales: { x: { grid: { display: false } }, y: { beginAtZero: true, border: { display: false }, ticks: { callback: v => v >= 1000 ? (v / 1000) + ' mil' : v } }, y1: { beginAtZero: true, position: 'right', grid: { display: false }, border: { display: false } } }
    }
  });
  const suc = <?= json_encode(array_map(fn($s) => ['n' => $s['nombre'], 'm' => round((float)$s['monto'], 2)], $porSucursal), JSON_UNESCAPED_UNICODE) ?>;
  new Chart(document.getElementById('chartSucursal'), {
    type: 'doughnut',
    data: { labels: suc.map(s => s.n), datasets: [{ data: suc.some(s => s.m > 0) ? suc.map(s => s.m) : suc.map(() => 1), backgroundColor: ['#0F536B', '#3FA7BF', '#8DC8D6', '#092E3D', '#29809B', '#D7E9EF'], borderWidth: 3, borderColor: '#fff', hoverOffset: 6 }] },
    options: { maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' ' + Cristalia.fmtMoney(suc[c.dataIndex].m) } } } }
  });
});
</script>
