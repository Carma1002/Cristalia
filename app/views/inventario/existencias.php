<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0 me-auto">Existencias por sucursal</h1>
  <?php if (can('reportes.ver')): ?><a href="<?= url('reportes/inventario') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-bar-graph"></i> Reporte valorizado</a><?php endif; ?>
</div>
<form class="card card-body mb-3 py-2" method="get">
  <input type="hidden" name="r" value="existencias">
  <div class="row g-2">
    <div class="col-12 col-md-6"><input name="q" class="form-control form-control-sm" placeholder="Buscar producto" value="<?= e($f['q']) ?>"></div>
    <div class="col-8 col-md-4"><select name="tipo_id" class="form-select form-select-sm"><option value="">Todos los tipos</option>
      <?php foreach ($tipos as $t): ?><option value="<?= $t['id'] ?>" <?= selected($t['id'], $f['tipo_id']) ?>><?= e($t['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-4 col-md-2"><button class="btn btn-sm btn-secondary w-100">Filtrar</button></div>
  </div>
</form>
<div class="card"><div class="table-responsive">
  <table class="table table-sm table-hover align-middle mb-0">
    <thead><tr><th>Producto</th><th class="text-end">Mín.</th>
      <?php foreach ($sucursales as $s): ?><th class="text-end"><?= e($s['nombre']) ?></th><?php endforeach; ?>
      <?php if (count($sucursales) > 1): ?><th class="text-end">Total</th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($rows as $p): $tot = 0; ?>
      <tr class="clickable" data-href="<?= url('productos/ver', ['id' => $p['id']]) ?>">
        <td><span class="text-muted small"><?= e($p['codigo']) ?></span><br><?= e($p['nombre']) ?></td>
        <td class="text-end text-muted"><?= (int)$p['stock_minimo'] ?></td>
        <?php foreach ($sucursales as $s): $c = $mapa[$p['id']][$s['id']] ?? 0; $tot += $c;
          $cls = $p['stock_minimo'] > 0 && $c <= $p['stock_minimo'] ? ($c == 0 ? 'bg-danger-subtle' : 'bg-warning-subtle') : ''; ?>
          <td class="text-end <?= $cls ?>"><?= num($c) ?></td>
        <?php endforeach; ?>
        <?php if (count($sucursales) > 1): ?><td class="text-end fw-bold"><?= num($tot) ?></td><?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div></div>
<div class="small text-muted mt-2"><span class="badge bg-warning-subtle text-dark">amarillo</span> en o bajo el mínimo · <span class="badge bg-danger-subtle text-dark">rojo</span> agotado</div>
<div class="mt-2"><?= paginacion_html($total, $lim, $page) ?></div>
