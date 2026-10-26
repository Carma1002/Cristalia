<?php
$extra = '<div class="col-6 col-md-2"><label class="form-label small mb-0">Agrupar por</label><select name="agrupar" class="form-select form-select-sm">';
foreach (['dia' => 'Día', 'mes' => 'Mes', 'sucursal' => 'Sucursal', 'vendedor' => 'Vendedor', 'metodo' => 'Método de pago'] as $k => $t) {
    $extra .= '<option value="' . $k . '" ' . selected($k, $agrupar) . '>' . $t . '</option>';
}
$extra .= '</select></div>';
echo View::partial('reportes/_barra', compact('title', 'desde', 'hasta', 'suc', 'sucursales', 'extra') + ['ruta' => 'reportes/ventas', 'fechas' => true]);
?>
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3"><div class="card card-body py-2"><div class="small text-muted">Ventas</div><div class="fs-5 fw-bold"><?= num($tot['ventas']) ?></div></div></div>
  <div class="col-6 col-md-3"><div class="card card-body py-2"><div class="small text-muted">Total vendido</div><div class="fs-5 fw-bold"><?= money($tot['total']) ?></div></div></div>
  <div class="col-6 col-md-3"><div class="card card-body py-2"><div class="small text-muted">Descuentos</div><div class="fs-5 fw-bold"><?= money($tot['descuento']) ?></div></div></div>
  <div class="col-6 col-md-3"><div class="card card-body py-2"><div class="small text-muted">Ticket promedio</div><div class="fs-5 fw-bold"><?= money($tot['ventas'] ? $tot['total'] / $tot['ventas'] : 0) ?></div></div></div>
</div>
<?php if ($rows): ?><div class="card mb-3"><div class="card-body"><canvas id="graf" height="90"></canvas></div></div><?php endif; ?>
<div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
  <thead><tr><th><?= e($etq) ?></th><th class="text-end">Ventas</th><th class="text-end">Subtotal</th><th class="text-end">Descuento</th><th class="text-end">Total</th></tr></thead>
  <tbody><?php foreach ($rows as $r): ?>
    <tr><td><?= e($agrupar === 'dia' ? fecha($r['grupo'], false) : $r['grupo']) ?></td><td class="text-end"><?= num($r['ventas']) ?></td>
      <td class="text-end"><?= money($r['subtotal']) ?></td><td class="text-end"><?= money($r['descuento']) ?></td><td class="text-end fw-semibold"><?= money($r['total']) ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-4">Sin ventas en el período.</td></tr><?php endif; ?></tbody>
  <tfoot><tr class="fw-bold"><td>Total</td><td class="text-end"><?= num($tot['ventas']) ?></td><td class="text-end"><?= money($tot['subtotal']) ?></td><td class="text-end"><?= money($tot['descuento']) ?></td><td class="text-end"><?= money($tot['total']) ?></td></tr></tfoot>
</table></div></div>
<?php if ($rows): ?>
<script src="assets/vendor/chartjs/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (!window.Chart) return;
  const d = <?= json_encode(array_map(fn($r) => ['g' => $agrupar === 'dia' ? fecha($r['grupo'], false) : $r['grupo'], 't' => round((float)$r['total'], 2)], $rows), JSON_UNESCAPED_UNICODE) ?>;
  new Chart(document.getElementById('graf'), { type: <?= json_encode(in_array($agrupar, ['dia', 'mes']) ? 'line' : 'bar') ?>,
    data: { labels: d.map(x => x.g), datasets: [{ label: 'Total vendido', data: d.map(x => x.t), backgroundColor: 'rgba(13,110,253,.6)', borderColor: '#0d6efd', fill: false, tension: .25 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });
});
</script>
<?php endif; ?>
