<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0 me-auto">Pedidos <span class="text-muted fs-6">(<?= num($total) ?>)</span></h1>
  <?php if (can('pedidos.gestionar')): ?><a href="<?= url('pedidos/crear') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nuevo pedido</a><?php endif; ?>
</div>
<div class="d-flex gap-1 flex-wrap mb-2">
  <?php foreach (['' => 'Todos', 'PENDIENTE' => 'Pendientes', 'CONFIRMADO' => 'Confirmados', 'COMPLETADO' => 'Completados', 'CANCELADO' => 'Cancelados'] as $k => $t): ?>
    <a href="<?= e(url_actual(['estado' => $k, 'p' => 1])) ?>" class="btn btn-sm <?= $f['estado'] === $k ? 'btn-dark' : 'btn-outline-secondary' ?>"><?= $t ?></a>
  <?php endforeach; ?>
</div>
<form class="card card-body mb-3 py-2" method="get">
  <input type="hidden" name="r" value="pedidos"><input type="hidden" name="estado" value="<?= e($f['estado']) ?>">
  <div class="row g-2 align-items-end">
    <div class="col-12 col-md-4"><input name="q" class="form-control form-control-sm" placeholder="N° de pedido, cliente o documento" value="<?= e($f['q']) ?>"></div>
    <div class="col-6 col-md-2"><input type="date" name="desde" class="form-control form-control-sm" value="<?= e($f['desde']) ?>" title="Desde"></div>
    <div class="col-6 col-md-2"><input type="date" name="hasta" class="form-control form-control-sm" value="<?= e($f['hasta']) ?>" title="Hasta"></div>
    <?php if (!Auth::sucursalFija()): ?>
    <div class="col-8 col-md-2"><select name="sucursal_id" class="form-select form-select-sm"><option value="">Todas las sucursales</option>
      <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $f['sucursal_id']) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
    <div class="col-4 col-md-2"><button class="btn btn-sm btn-secondary w-100">Filtrar</button></div>
  </div>
</form>
<div class="card"><div class="table-responsive">
  <table class="table table-sm table-hover align-middle mb-0">
    <thead><tr><th>N°</th><th>Fecha</th><th>Cliente</th><th class="d-none d-md-table-cell">Sucursal</th><th class="text-center d-none d-sm-table-cell">Ítems</th><th>Estado</th><th class="text-end">Total</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $p): ?>
      <tr class="clickable" data-href="<?= url('pedidos/ver', ['id' => $p['id']]) ?>">
        <td class="text-nowrap fw-semibold"><?= e($p['numero']) ?></td><td class="text-nowrap small"><?= fecha($p['fecha']) ?></td><td><?= e($p['cliente']) ?></td>
        <td class="d-none d-md-table-cell"><?= e($p['sucursal']) ?></td><td class="text-center d-none d-sm-table-cell"><?= (int)$p['items'] ?></td>
        <td><?= badge_estado($p['estado']) ?></td><td class="text-end text-nowrap"><?= money($p['total']) ?></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">Sin pedidos.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div></div>
<div class="mt-3"><?= paginacion_html($total, $lim, $page) ?></div>
