<?= View::partial('reportes/_barra', compact('title', 'desde', 'hasta', 'suc', 'sucursales') + ['ruta' => 'reportes/productos', 'fechas' => true]) ?>
<?php $max = $rows ? max(array_column($rows, 'unidades')) : 1; ?>
<div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0 align-middle">
  <thead><tr><th>#</th><th>Producto</th><th class="text-end">Unidades</th><th class="d-none d-md-table-cell" style="width:30%"></th><th class="text-end">Monto</th></tr></thead>
  <tbody><?php foreach ($rows as $i => $r): ?>
    <tr><td><?= $i + 1 ?></td><td><span class="small text-muted"><?= e($r['codigo']) ?></span> <?= e($r['nombre']) ?></td><td class="text-end"><?= num($r['unidades']) ?></td>
      <td class="d-none d-md-table-cell"><div class="progress" style="height:8px"><div class="progress-bar" style="width:<?= round($r['unidades'] / $max * 100) ?>%"></div></div></td>
      <td class="text-end"><?= money($r['monto']) ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-4">Sin ventas en el período.</td></tr><?php endif; ?></tbody>
  <tfoot><tr class="fw-bold"><td></td><td>Total</td><td class="text-end"><?= num(array_sum(array_column($rows, 'unidades'))) ?></td><td class="d-none d-md-table-cell"></td><td class="text-end"><?= money(array_sum(array_column($rows, 'monto'))) ?></td></tr></tfoot>
</table></div></div>
