<h1 class="h4 mb-3">Bitácora de operaciones <span class="text-muted fs-6">(<?= num($total) ?>)</span></h1>
<form class="card card-body mb-3 py-2" method="get">
  <input type="hidden" name="r" value="bitacora">
  <div class="row g-2 align-items-end">
    <div class="col-6 col-md-2"><select name="modulo" class="form-select form-select-sm"><option value="">Módulo</option>
      <?php foreach ($modulos as $m): ?><option <?= selected($m, input('modulo')) ?>><?= e($m) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-2"><select name="usuario_id" class="form-select form-select-sm"><option value="">Usuario</option>
      <?php foreach ($usuarios as $u): ?><option value="<?= $u['id'] ?>" <?= selected($u['id'], input('usuario_id')) ?>><?= e($u['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-2"><input type="date" name="desde" class="form-control form-control-sm" value="<?= e(input('desde')) ?>"></div>
    <div class="col-6 col-md-2"><input type="date" name="hasta" class="form-control form-control-sm" value="<?= e(input('hasta')) ?>"></div>
    <div class="col-8 col-md-2"><input name="q" class="form-control form-control-sm" placeholder="Texto" value="<?= e(input('q')) ?>"></div>
    <div class="col-4 col-md-2"><button class="btn btn-sm btn-secondary w-100">Filtrar</button></div>
  </div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0 small">
  <thead><tr><th>Fecha</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>Detalle</th><th class="d-none d-lg-table-cell">IP</th></tr></thead>
  <tbody><?php foreach ($rows as $b): ?>
    <tr class="<?= str_contains($b['accion'], 'fallido') || str_contains($b['accion'], 'anular') || str_contains($b['accion'], 'restaurar') ? 'table-warning' : '' ?>">
      <td class="text-nowrap"><?= fecha($b['fecha']) ?></td><td><?= e($b['usuario'] ?? '—') ?></td><td><?= e($b['modulo']) ?></td>
      <td><code><?= e($b['accion']) ?></code></td><td><?= e($b['detalle']) ?></td><td class="d-none d-lg-table-cell"><?= e($b['ip']) ?></td></tr>
  <?php endforeach; ?></tbody>
</table></div></div>
<div class="mt-3"><?= paginacion_html($total, $lim, $page) ?></div>
