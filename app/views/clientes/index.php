<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0 me-auto">Clientes <span class="text-muted fs-6">(<?= num($total) ?>)</span></h1>
  <?php if (can('clientes.gestionar')): ?><a href="<?= url('clientes/crear') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nuevo cliente</a><?php endif; ?>
</div>
<form class="card card-body mb-3 py-2" method="get">
  <input type="hidden" name="r" value="clientes">
  <div class="row g-2">
    <div class="col-12 col-md-7"><input name="q" class="form-control form-control-sm" placeholder="Nombre, documento, teléfono o correo" value="<?= e($q) ?>"></div>
    <div class="col-8 col-md-3"><select name="activo" class="form-select form-select-sm">
      <option value="1" <?= selected('1', $activo) ?>>Activos</option><option value="0" <?= selected('0', $activo) ?>>Inactivos</option><option value="" <?= selected('', $activo) ?>>Todos</option></select></div>
    <div class="col-4 col-md-2"><button class="btn btn-sm btn-secondary w-100">Buscar</button></div>
  </div>
</form>
<div class="card"><div class="table-responsive">
  <table class="table table-sm table-hover align-middle mb-0">
    <thead><tr><th>Documento</th><th>Nombre</th><th class="d-none d-md-table-cell">Teléfono</th><th class="d-none d-lg-table-cell">Correo</th><th class="text-end">Compras</th><th class="text-end d-none d-sm-table-cell">Total comprado</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $c): ?>
      <tr class="clickable <?= $c['activo'] ? '' : 'text-muted' ?>" data-href="<?= url('clientes/ver', ['id' => $c['id']]) ?>">
        <td class="text-nowrap"><?= e(Cliente::documento($c)) ?></td><td><?= e($c['nombre']) ?></td>
        <td class="d-none d-md-table-cell"><?= e($c['telefono']) ?></td><td class="d-none d-lg-table-cell"><?= e($c['email']) ?></td>
        <td class="text-end"><?= (int)$c['compras'] ?></td><td class="text-end d-none d-sm-table-cell"><?= money($c['total_comprado']) ?></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">Sin resultados.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div></div>
<div class="mt-3"><?= paginacion_html($total, $lim, $page) ?></div>
