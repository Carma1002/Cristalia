<?= View::partial('reportes/_barra', compact('title', 'desde', 'hasta', 'suc', 'sucursales') + ['ruta' => 'reportes/rotacion', 'fechas' => true]) ?>
<div class="alert alert-light border small py-2">
  <strong>Rotación</strong> = unidades vendidas ÷ existencia promedio del período (promedio entre la existencia inicial, reconstruida desde el kardex, y la final).
  <strong>Días de inventario</strong> = días del período ÷ rotación. Mayor rotación = el producto se vende más rápido.
</div>
<div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
  <thead><tr><th>Producto</th><th class="text-end d-none d-md-table-cell">Exist. inicial</th><th class="text-end d-none d-md-table-cell">Exist. final</th>
    <th class="text-end">Promedio</th><th class="text-end">Vendidas</th><th class="text-end">Rotación</th><th class="text-end">Días inv.</th></tr></thead>
  <tbody><?php foreach ($rows as $r): ?>
    <tr><td><span class="small text-muted"><?= e($r['codigo']) ?></span> <?= e($r['nombre']) ?></td>
      <td class="text-end d-none d-md-table-cell"><?= (int)$r['stock_inicial'] ?></td><td class="text-end d-none d-md-table-cell"><?= (int)$r['stock_final'] ?></td>
      <td class="text-end"><?= num($r['stock_promedio'], 1) ?></td><td class="text-end"><?= (int)$r['vendidas'] ?></td>
      <td class="text-end fw-semibold"><?= e($r['rot_txt']) ?></td><td class="text-end"><?= e($r['dias_txt']) ?></td></tr>
  <?php endforeach; ?></tbody>
</table></div></div>
