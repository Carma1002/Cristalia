<?php
$extra = '<div><label class="form-label small mb-1">Agrupar por</label><select name="agrupar" class="form-select">';
foreach (['dia' => 'Día', 'mes' => 'Mes', 'sucursal' => 'Sucursal', 'vendedor' => 'Vendedor', 'metodo' => 'Método de pago'] as $k => $t) $extra .= '<option value="' . $k . '" ' . selected($k, $agrupar) . '>' . $t . '</option>';
$extra .= '</select></div>';
$mon = ajuste('moneda', '$') . ' ';
$ticket = $tot['ventas'] ? $tot['total'] / $tot['ventas'] : 0;
$maxTop = $top ? max(array_column($top, 'unidades')) : 1;
?>
<?= ui_header('Ventas', 'Totales por período, sucursal, vendedor o método de pago, y los productos que más se venden.', botones_exportar()) ?>
<?= View::partial('reportes/_filtros', compact('desde', 'hasta', 'suc', 'sucursales', 'extra') + ['ruta' => 'reportes']) ?>

<div class="row g-3 mb-3 c-stagger">
  <div class="col-6 col-xl-3"><div class="c-card c-kpi"><span class="c-kpi__label">Ventas registradas</span><div class="c-kpi__value" data-count="<?= $tot['ventas'] ?>"><?= num($tot['ventas']) ?></div></div></div>
  <div class="col-6 col-xl-3"><div class="c-card c-kpi"><span class="c-kpi__label">Total vendido</span><div class="c-kpi__value" style="color:var(--c-azul)" data-count="<?= $tot['total'] ?>" data-decimals="2" data-prefix="<?= e($mon) ?>"><?= money($tot['total']) ?></div></div></div>
  <div class="col-6 col-xl-3"><div class="c-card c-kpi"><span class="c-kpi__label">Venta promedio</span><div class="c-kpi__value" data-count="<?= $ticket ?>" data-decimals="2" data-prefix="<?= e($mon) ?>"><?= money($ticket) ?></div></div></div>
  <div class="col-6 col-xl-3"><div class="c-card c-kpi"><span class="c-kpi__label">Descuentos otorgados</span><div class="c-kpi__value" data-count="<?= $tot['descuento'] ?>" data-decimals="2" data-prefix="<?= e($mon) ?>"><?= money($tot['descuento']) ?></div></div></div>
</div>

<div class="row g-3 c-stagger">
  <div class="col-xl-7">
    <div class="c-card h-100">
      <div class="c-card__head"><div><h2 class="c-card__title">Ventas por <?= e(mb_strtolower($etq)) ?></h2><p class="c-card__sub"><?= fecha($desde, false) ?> – <?= fecha($hasta, false) ?></p></div></div>
      <?php if ($rows): ?>
        <div class="c-card__body pb-0"><div style="height:240px"><canvas id="graf" aria-label="Gráfico de ventas"></canvas></div></div>
        <div class="c-table-wrap" style="max-height:420px"><table class="c-table">
          <thead><tr><th><?= e($etq) ?></th><th class="num">Ventas</th><th class="num c-hide-xs">Subtotal</th><th class="num c-hide-xs">Descuento</th><th class="num">Total</th></tr></thead>
          <tbody><?php foreach ($rows as $r): ?>
            <tr><td><?= e($agrupar === 'dia' ? fecha($r['grupo'], false) : $r['grupo']) ?></td><td class="num"><?= num($r['ventas']) ?></td>
              <td class="num c-hide-xs"><?= money($r['subtotal']) ?></td><td class="num c-hide-xs text-muted"><?= money($r['descuento']) ?></td><td class="num strong"><?= money($r['total']) ?></td></tr>
          <?php endforeach; ?></tbody>
          <tfoot><tr><td>Total</td><td class="num"><?= num($tot['ventas']) ?></td><td class="num c-hide-xs"><?= money($tot['subtotal']) ?></td><td class="num c-hide-xs"><?= money($tot['descuento']) ?></td><td class="num"><?= money($tot['total']) ?></td></tr></tfoot>
        </table></div>
      <?php else: ?><?= empty_state('graph-up', 'Sin ventas en este período', 'Elija otras fechas para ver resultados.') ?><?php endif; ?>
    </div>
  </div>
  <div class="col-xl-5" id="productos">
    <div class="c-card h-100">
      <div class="c-card__head"><div><h2 class="c-card__title">Productos más vendidos</h2><p class="c-card__sub">Unidades y monto en el período</p></div><?= $top ? botones_exportar(['tabla' => 'productos']) : '' ?></div>
      <div class="c-card__body">
        <?php foreach (array_slice($top, 0, 12) as $i => $t): ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between gap-2 small mb-1"><span class="text-truncate"><b class="me-1" style="color:var(--c-cian-texto)"><?= $i + 1 ?></b><?= e($t['nombre']) ?></span>
              <span class="text-nowrap"><b><?= num($t['unidades']) ?> u</b> <span class="text-muted">· <?= money($t['monto']) ?></span></span></div>
            <div class="c-bar"><span class="<?= $i < 3 ? 'is-cian' : '' ?>" style="width:<?= round($t['unidades'] / $maxTop * 100) ?>%;animation-delay:<?= $i * 60 ?>ms"></span></div>
          </div>
        <?php endforeach; ?>
        <?php if (count($top) > 12): ?><p class="small text-muted mb-0">Y <?= count($top) - 12 ?> productos más. Descargue el Excel para ver la lista completa.</p><?php endif; ?>
        <?php if (!$top): ?><?= empty_state('trophy', 'Aún no hay productos vendidos') ?><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if ($rows): ?>
<script src="assets/vendor/chartjs/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (!window.Chart) return;
  Cristalia.chartTheme();
  const d = <?= json_encode(array_map(fn($r) => ['g' => $agrupar === 'dia' ? date('d/m', strtotime($r['grupo'])) : $r['grupo'], 't' => round((float)$r['total'], 2)], $rows), JSON_UNESCAPED_UNICODE) ?>;
  const linea = <?= json_encode(in_array($agrupar, ['dia', 'mes'], true)) ?>;
  const ctx = document.getElementById('graf').getContext('2d');
  const grad = ctx.createLinearGradient(0, 0, 0, 240); grad.addColorStop(0, 'rgba(63,167,191,.35)'); grad.addColorStop(1, 'rgba(63,167,191,0)');
  new Chart(ctx, {
    type: linea ? 'line' : 'bar',
    data: { labels: d.map(x => x.g), datasets: [{ label: 'Total vendido', data: d.map(x => x.t),
      backgroundColor: linea ? grad : Cristalia.escala.slice(0, 5), borderColor: '#0F536B', borderWidth: linea ? 2.5 : 0, fill: linea, tension: .35, pointRadius: d.length > 40 ? 0 : 3, pointBackgroundColor: '#fff', borderRadius: 8, maxBarThickness: 46 }] },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' ' + Cristalia.fmtMoney(c.parsed.y) } } },
      scales: { x: { grid: { display: false } }, y: { beginAtZero: true, border: { display: false }, ticks: { callback: v => v >= 1000 ? (v / 1000) + ' mil' : v } } } }
  });
});
</script>
<?php endif; ?>
