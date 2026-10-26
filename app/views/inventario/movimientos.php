<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0 me-auto">Movimientos de inventario</h1>
  <?php if (can('inventario.movimientos')): ?>
    <a href="<?= url('movimientos/nuevo') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Registrar movimiento</a>
  <?php endif; ?>
</div>
<form class="card card-body mb-3 py-2" method="get">
  <input type="hidden" name="r" value="movimientos">
  <?php if (input('producto_id')): ?><input type="hidden" name="producto_id" value="<?= (int)input('producto_id') ?>"><?php endif; ?>
  <div class="row g-2 align-items-end">
    <div class="col-6 col-md-2"><label class="form-label small mb-0">Desde</label><input type="date" name="desde" class="form-control form-control-sm" value="<?= e($desde) ?>"></div>
    <div class="col-6 col-md-2"><label class="form-label small mb-0">Hasta</label><input type="date" name="hasta" class="form-control form-control-sm" value="<?= e($hasta) ?>"></div>
    <div class="col-6 col-md-2"><select name="tipo" class="form-select form-select-sm"><option value="">Todos los tipos</option>
      <?php foreach (['ENTRADA', 'SALIDA', 'AJUSTE', 'VENTA', 'ANULACION'] as $t): ?><option <?= selected($t, $tipo) ?>><?= $t ?></option><?php endforeach; ?></select></div>
    <?php if (!Auth::sucursalFija()): ?>
    <div class="col-6 col-md-3"><select name="sucursal_id" class="form-select form-select-sm"><option value="">Todas las sucursales</option>
      <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $suc) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
    <div class="col-12 col-md-2"><button class="btn btn-sm btn-secondary w-100">Filtrar</button></div>
  </div>
</form>
<?php if (input('producto_id') && $rows): ?><div class="alert alert-info py-2">Kardex de <strong><?= e($rows[0]['codigo'] . ' · ' . $rows[0]['nombre']) ?></strong> · <a href="<?= url('movimientos') ?>">quitar filtro</a></div><?php endif; ?>
<div class="card"><div class="table-responsive">
  <table class="table table-sm table-hover align-middle mb-0">
    <thead><tr><th>Fecha</th><th>Tipo</th><th>Producto</th><th class="d-none d-md-table-cell">Sucursal</th><th class="text-end">Cant.</th>
      <th class="text-end d-none d-sm-table-cell">Antes</th><th class="text-end">Después</th><th class="d-none d-lg-table-cell">Motivo / referencia</th><th class="d-none d-lg-table-cell">Usuario</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $m): ?>
      <tr><td class="text-nowrap small"><?= fecha($m['fecha']) ?></td><td><?= badge_estado($m['tipo']) ?></td>
        <td><span class="small text-muted"><?= e($m['codigo']) ?></span> <?= e($m['nombre']) ?></td>
        <td class="d-none d-md-table-cell"><?= e($m['sucursal']) ?></td>
        <td class="text-end fw-semibold <?= $m['cantidad'] < 0 ? 'text-danger' : 'text-success' ?>"><?= sprintf('%+d', $m['cantidad']) ?></td>
        <td class="text-end d-none d-sm-table-cell text-muted"><?= (int)$m['stock_anterior'] ?></td>
        <td class="text-end"><?= (int)$m['stock_resultante'] ?></td>
        <td class="d-none d-lg-table-cell small"><?= e($m['motivo']) ?></td><td class="d-none d-lg-table-cell small"><?= e($m['usuario']) ?></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">Sin movimientos en el período.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div></div>
<p class="small text-muted mt-2">Se muestran hasta 500 registros. Para más, use <a href="<?= url('reportes/movimientos') ?>">Reportes → Movimientos</a> y exporte.</p>
