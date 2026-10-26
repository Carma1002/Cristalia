<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0 me-auto">Roles y permisos</h1>
  <a href="<?= url('roles/crear') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nuevo rol</a>
</div>
<div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
  <thead><tr><th>Rol</th><th class="d-none d-md-table-cell">Descripción</th><th class="text-center">Usuarios</th><th class="text-center">Permisos</th><th></th></tr></thead>
  <tbody><?php foreach ($roles as $r): ?>
    <tr><td class="fw-semibold"><?= e($r['nombre']) ?> <?= $r['es_sistema'] ? '<span class="badge bg-dark">sistema</span>' : '' ?></td>
      <td class="d-none d-md-table-cell small"><?= e($r['descripcion']) ?></td><td class="text-center"><?= (int)$r['usuarios'] ?></td>
      <td class="text-center"><?= $r['es_sistema'] ? 'Todos' : (int)$r['permisos'] ?></td>
      <td class="text-end text-nowrap"><a href="<?= url('roles/editar', ['id' => $r['id']]) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
        <?php if (!$r['es_sistema'] && !$r['usuarios']): ?>
        <form method="post" action="<?= url('roles/eliminar') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este rol?')"><?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
        <?php endif; ?></td></tr>
  <?php endforeach; ?></tbody>
</table></div></div>
