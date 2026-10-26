<?php
$extra = '<div class="col-6 col-md-3"><label class="form-label small mb-0">Tipo</label><select name="tipo_id" class="form-select form-select-sm"><option value="">Todos</option>';
foreach ($tipos as $t) $extra .= '<option value="' . $t['id'] . '" ' . selected($t['id'], $tipo) . '>' . e($t['nombre']) . '</option>';
$extra .= '</select></div><div class="col-6 col-md-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="bajo" value="1" id="bajo" ' . checked($bajo) . '><label class="form-check-label small" for="bajo">Solo stock bajo</label></div></div>';
echo View::partial('reportes/_barra', compact('title', 'suc', 'sucursales', 'extra') + ['ruta' => 'reportes/inventario', 'fechas' => false, 'desde' => '', 'hasta' => '']);
?>
<div class="row g-3 mb-3">
  <div class="col-4"><div class="card card-body py-2"><div class="small text-muted">Unidades</div><div class="fs-5 fw-bold"><?= num($tot['unidades']) ?></div></div></div>
  <div class="col-4"><div class="card card-body py-2"><div class="small text-muted">Valor al costo</div><div class="fs-5 fw-bold"><?= money($tot['costo']) ?></div></div></div>
  <div class="col-4"><div class="card card-body py-2"><div class="small text-muted">Valor de venta</div><div class="fs-5 fw-bold"><?= money($tot['venta']) ?></div></div></div>
</div>
<div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
  <thead><tr><th>Código</th><th>Producto</th><th class="d-none d-lg-table-cell">Tipo</th><th>Sucursal</th><th class="text-end">Exist.</th><th class="text-end">Mín.</th><th class="text-end d-none d-md-table-cell">Valor costo</th><th class="text-end d-none d-md-table-cell">Valor venta</th></tr></thead>
  <tbody><?php foreach ($rows as $r): $b = $r['stock_minimo'] > 0 && $r['cantidad'] <= $r['stock_minimo']; ?>
    <tr><td class="text-nowrap"><?= e($r['codigo']) ?></td><td><?= e($r['nombre']) ?></td><td class="d-none d-lg-table-cell"><?= e($r['tipo']) ?></td><td><?= e($r['sucursal']) ?></td>
      <td class="text-end <?= $b ? 'text-danger fw-bold' : '' ?>"><?= num($r['cantidad']) ?></td><td class="text-end"><?= (int)$r['stock_minimo'] ?></td>
      <td class="text-end d-none d-md-table-cell"><?= money($r['valor_costo']) ?></td><td class="text-end d-none d-md-table-cell"><?= money($r['valor_venta']) ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">Sin datos.</td></tr><?php endif; ?></tbody>
</table></div></div>
