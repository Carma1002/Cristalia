<?php
$extra = '<div class="col-6 col-md-2"><label class="form-label small mb-0">Tipo</label><select name="tipo" class="form-select form-select-sm"><option value="">Todos</option>';
foreach (['ENTRADA', 'SALIDA', 'AJUSTE', 'VENTA', 'ANULACION'] as $t) $extra .= '<option ' . selected($t, $tipo) . '>' . $t . '</option>';
$extra .= '</select></div>';
echo View::partial('reportes/_barra', compact('title', 'desde', 'hasta', 'suc', 'sucursales', 'extra') + ['ruta' => 'reportes/movimientos', 'fechas' => true]);
$resumen = [];
foreach ($rows as $r) { $resumen[$r['tipo']] = ($resumen[$r['tipo']] ?? 0) + (int)$r['cantidad']; }
?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <?php foreach ($resumen as $t => $c): ?><span class="border rounded px-2 py-1 small"><?= badge_estado($t) ?> <?= sprintf('%+d', $c) ?> u</span><?php endforeach; ?>
</div>
<div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
  <thead><tr><th>Fecha</th><th>Tipo</th><th>Producto</th><th class="d-none d-md-table-cell">Sucursal</th><th class="text-end">Cant.</th><th class="text-end">Saldo</th><th class="d-none d-lg-table-cell">Motivo</th><th class="d-none d-lg-table-cell">Usuario</th></tr></thead>
  <tbody><?php foreach ($rows as $m): ?>
    <tr><td class="small text-nowrap"><?= e($m['fecha_f']) ?></td><td><?= badge_estado($m['tipo']) ?></td><td><?= e($m['codigo']) ?> · <?= e($m['nombre']) ?></td>
      <td class="d-none d-md-table-cell"><?= e($m['sucursal']) ?></td><td class="text-end <?= $m['cantidad'] < 0 ? 'text-danger' : 'text-success' ?>"><?= sprintf('%+d', $m['cantidad']) ?></td>
      <td class="text-end"><?= (int)$m['stock_resultante'] ?></td><td class="d-none d-lg-table-cell small"><?= e($m['motivo']) ?></td><td class="d-none d-lg-table-cell small"><?= e($m['usuario']) ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">Sin movimientos.</td></tr><?php endif; ?></tbody>
</table></div></div>
