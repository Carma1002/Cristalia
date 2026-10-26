<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0 me-auto"><i class="bi bi-exclamation-triangle text-danger"></i> Alertas de stock mínimo</h1>
  <?php if (!Auth::sucursalFija()): ?>
  <form method="get"><input type="hidden" name="r" value="stock-bajo">
    <select name="sucursal_id" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">Todas las sucursales</option>
      <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $suc) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></form>
  <?php endif; ?>
  <?php if (can('reportes.ver')): ?>
    <a class="btn btn-sm btn-outline-danger" href="<?= url('reportes/inventario', ['bajo' => 1, 'sucursal_id' => $suc, 'formato' => 'pdf']) ?>" target="_blank"><i class="bi bi-filetype-pdf"></i> PDF</a>
  <?php endif; ?>
</div>
<div class="card"><div class="table-responsive">
  <table class="table table-sm table-hover align-middle mb-0">
    <thead><tr><th>Código</th><th>Producto</th><th>Sucursal</th><th class="text-end">Existencia</th><th class="text-end">Mínimo</th><th class="text-end">Faltante</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['codigo']) ?></td><td><?= e($r['nombre']) ?></td><td><?= e($r['sucursal']) ?></td>
        <td class="text-end fw-bold <?= $r['cantidad'] == 0 ? 'text-danger' : 'text-warning' ?>"><?= (int)$r['cantidad'] ?><?= $r['cantidad'] == 0 ? ' <span class="badge bg-danger">agotado</span>' : '' ?></td>
        <td class="text-end"><?= (int)$r['stock_minimo'] ?></td>
        <td class="text-end"><?= max(0, $r['stock_minimo'] - $r['cantidad'] + 1) ?></td>
        <td class="text-end"><?php if (can('inventario.movimientos')): ?>
          <a class="btn btn-sm btn-success" href="<?= url('movimientos/nuevo', ['producto_id' => $r['id'], 'sucursal_id' => $r['sucursal_id'], 'tipo' => 'ENTRADA']) ?>"><i class="bi bi-plus"></i> Entrada</a>
        <?php endif; ?></td>
      </tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No hay productos en o bajo el stock mínimo. 👍</td></tr><?php endif; ?>
    </tbody>
  </table>
</div></div>
<p class="small text-muted mt-2">“Faltante” = unidades necesarias para superar el mínimo.</p>
