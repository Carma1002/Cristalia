<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0 me-auto">Productos <span class="text-muted fs-6">(<?= num($total) ?>)</span></h1>
  <?php if (can('inventario.movimientos')): ?><a href="<?= url('movimientos/nuevo') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left-right"></i> Movimiento</a><?php endif; ?>
  <?php if (can('inventario.gestionar')): ?><a href="<?= url('productos/crear') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nuevo producto</a><?php endif; ?>
</div>

<form class="card card-body mb-3 py-2" method="get">
  <input type="hidden" name="r" value="productos">
  <div class="row g-2">
    <div class="col-12 col-md-4"><input name="q" class="form-control form-control-sm" placeholder="Código, nombre o descripción" value="<?= e($f['q']) ?>"></div>
    <div class="col-6 col-md-2"><select name="tipo_id" class="form-select form-select-sm"><option value="">Tipo</option>
      <?php foreach ($tipos as $t): ?><option value="<?= $t['id'] ?>" <?= selected($t['id'], $f['tipo_id']) ?>><?= e($t['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-2"><select name="marca" class="form-select form-select-sm"><option value="">Marca</option>
      <?php foreach ($marcas as $m): ?><option <?= selected($m, $f['marca']) ?>><?= e($m) ?></option><?php endforeach; ?></select></div>
    <?php if (!Auth::sucursalFija()): ?>
    <div class="col-6 col-md-2"><select name="sucursal_id" class="form-select form-select-sm"><option value="">Stock: todas</option>
      <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $f['sucursal_id']) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
    <div class="col-6 col-md-1"><select name="activo" class="form-select form-select-sm">
      <option value="1" <?= selected('1', $f['activo']) ?>>Activos</option><option value="0" <?= selected('0', $f['activo']) ?>>Inactivos</option><option value="" <?= selected('', $f['activo']) ?>>Todos</option></select></div>
    <div class="col-12 col-md-1"><button class="btn btn-sm btn-secondary w-100"><i class="bi bi-search"></i></button></div>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover table-sm align-middle mb-0">
      <thead><tr><th>Código</th><th>Producto</th><th class="d-none d-md-table-cell">Vehículo</th><th class="d-none d-lg-table-cell">Tipo</th>
        <th class="text-end">Precio</th><th class="text-end">Exist.</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $p): $bajo = $p['stock_minimo'] > 0 && $p['stock'] <= $p['stock_minimo']; ?>
        <tr class="clickable <?= $p['activo'] ? '' : 'text-muted' ?>" data-href="<?= url('productos/ver', ['id' => $p['id']]) ?>">
          <td class="text-nowrap"><?= e($p['codigo']) ?></td>
          <td><?= e($p['nombre']) ?><?= $p['activo'] ? '' : ' <span class="badge bg-secondary">inactivo</span>' ?></td>
          <td class="d-none d-md-table-cell"><?= e(trim($p['marca_vehiculo'] . ' ' . $p['modelo_vehiculo'])) ?>
            <?php if ($p['anio_desde']): ?><small class="text-muted"><?= $p['anio_desde'] ?>–<?= $p['anio_hasta'] ?: 'act.' ?></small><?php endif; ?></td>
          <td class="d-none d-lg-table-cell"><?= e($p['tipo_nombre']) ?></td>
          <td class="text-end text-nowrap"><?= money($p['precio']) ?></td>
          <td class="text-end fw-semibold <?= $bajo ? 'text-danger' : '' ?>"><?= num($p['stock']) ?><?= $bajo ? ' <i class="bi bi-exclamation-triangle"></i>' : '' ?></td>
        </tr>
      <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">No hay productos con esos filtros.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="mt-3"><?= paginacion_html($total, $lim, $page) ?></div>
