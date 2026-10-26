<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0 me-auto">Usuarios</h1>
  <a href="<?= url('usuarios/crear') ?>" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> Nuevo usuario</a>
</div>
<div class="card"><div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">
  <thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th class="d-none d-md-table-cell">Sucursal</th><th class="d-none d-lg-table-cell">Último acceso</th><th>Estado</th><th></th></tr></thead>
  <tbody><?php foreach ($rows as $u): ?>
    <tr class="<?= $u['activo'] ? '' : 'text-muted' ?>">
      <td><?= e($u['nombre']) ?></td><td><?= e($u['usuario']) ?></td><td><?= e($u['rol']) ?></td>
      <td class="d-none d-md-table-cell"><?= e($u['sucursal'] ?? 'Todas') ?></td><td class="d-none d-lg-table-cell small"><?= fecha($u['ultimo_acceso']) ?></td>
      <td><?= $u['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?>
        <?= $u['bloqueado_hasta'] && strtotime($u['bloqueado_hasta']) > time() ? '<span class="badge bg-danger">Bloqueado</span>' : '' ?></td>
      <td class="text-end text-nowrap">
        <a href="<?= url('usuarios/editar', ['id' => $u['id']]) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
        <?php if ((int)$u['id'] !== Auth::id()): ?>
        <form method="post" action="<?= url('usuarios/estado') ?>" class="d-inline" onsubmit="return confirm('¿Cambiar el estado de este usuario?')">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>">
          <button class="btn btn-sm btn-outline-<?= $u['activo'] ? 'danger' : 'success' ?>" title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>"><i class="bi bi-<?= $u['activo'] ? 'person-x' : 'person-check' ?>"></i></button>
        </form>
        <?php endif; ?>
      </td></tr>
  <?php endforeach; ?></tbody>
</table></div></div>
