<?php
$tab = input('tab', can('admin.usuarios') ? 'usuarios' : 'permisos');
$acc = can('admin.usuarios') ? '<a href="' . url('usuarios/crear') . '" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nuevo usuario</a>' : '';
$editables = array_values(array_filter($roles, fn($r) => !$r['es_sistema']));
?>
<?= ui_header('Usuarios y permisos', 'Quién entra al sistema y qué puede hacer cada rol.', $acc) ?>

<div class="c-seg mb-3 reveal" style="--d:1">
  <?php if (can('admin.usuarios')): ?><a href="<?= url('usuarios') ?>" class="<?= $tab === 'usuarios' ? 'is-active' : '' ?>"><i class="bi bi-people"></i> Usuarios <span class="cnt"><?= count($rows) ?></span></a><?php endif; ?>
  <?php if (can('admin.roles')): ?><a href="<?= url('usuarios', ['tab' => 'permisos']) ?>" class="<?= $tab === 'permisos' ? 'is-active' : '' ?>"><i class="bi bi-shield-check"></i> Permisos por rol</a><?php endif; ?>
</div>

<?php if ($tab === 'usuarios' && can('admin.usuarios')): ?>
<div class="c-card reveal" style="--d:2"><div class="c-table-wrap"><table class="c-table">
  <thead><tr><th>Usuario</th><th>Rol</th><th class="c-hide-xs">Sucursal</th><th class="c-hide-xs">Último acceso</th><th>Estado</th><th></th></tr></thead>
  <tbody><?php foreach ($rows as $u): $bloq = $u['bloqueado_hasta'] && strtotime($u['bloqueado_hasta']) > time(); ?>
    <tr class="<?= $u['activo'] ? '' : 'opacity-50' ?>">
      <td><div class="d-flex align-items-center gap-3"><span class="c-avatar"><?= e(iniciales($u['nombre'])) ?></span>
        <div class="min-w-0"><div class="strong text-truncate"><?= e($u['nombre']) ?></div><small class="text-muted">@<?= e($u['usuario']) ?></small></div></div></td>
      <td><?= e($u['rol']) ?></td><td class="c-hide-xs"><?= e($u['sucursal'] ?? 'Todas') ?></td><td class="c-hide-xs small"><?= fecha($u['ultimo_acceso']) ?></td>
      <td><?= $bloq ? '<span class="c-pill c-pill--bad">Bloqueado</span>' : ($u['activo'] ? '<span class="c-pill c-pill--ok">Activo</span>' : '<span class="c-pill c-pill--muted">Inactivo</span>') ?></td>
      <td class="text-end text-nowrap">
        <a href="<?= url('usuarios/editar', ['id' => $u['id']]) ?>" class="btn btn-light btn-sm" title="Editar"><i class="bi bi-pencil"></i><span class="c-hide-xs">Editar</span></a>
        <?php if ((int)$u['id'] !== Auth::id()): ?>
        <form method="post" action="<?= url('usuarios/estado') ?>" class="d-inline" data-confirm="<?= $u['activo'] ? '¿Desactivar a ' . e($u['nombre']) . '?' : '¿Activar a ' . e($u['nombre']) . '?' ?>" data-confirm-text="<?= $u['activo'] ? 'No podrá entrar al sistema hasta que lo vuelva a activar.' : 'Podrá entrar de nuevo con su contraseña.' ?>" data-confirm-ok="<?= $u['activo'] ? 'Sí, desactivar' : 'Sí, activar' ?>" data-confirm-tone="<?= $u['activo'] ? 'danger' : '' ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>">
          <button class="btn btn-light btn-sm" title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>"><i class="bi bi-<?= $u['activo'] ? 'person-dash' : 'person-check' ?>"></i></button>
        </form>
        <?php endif; ?>
      </td></tr>
  <?php endforeach; ?></tbody>
</table></div></div>
<?php endif; ?>

<?php if ($tab === 'permisos' && can('admin.roles')): ?>
<form method="post" action="<?= url('roles/guardar') ?>" class="c-card reveal" style="--d:2">
  <?= csrf_field() ?>
  <?php foreach ($editables as $r): ?><input type="hidden" name="roles[]" value="<?= $r['id'] ?>"><?php endforeach; ?>
  <div class="c-card__head"><div><h2 class="c-card__title">¿Qué puede hacer cada rol?</h2><p class="c-card__sub">Marque las casillas y guarde. El Administrador siempre tiene acceso total.</p></div>
    <button class="btn btn-primary"><i class="bi bi-check2"></i> Guardar permisos</button></div>
  <div class="c-table-wrap mt-2"><table class="c-table">
    <thead><tr><th>Permiso</th>
      <?php foreach ($roles as $r): ?><th class="text-center" style="min-width:120px"><?= e($r['nombre']) ?><div class="fw-normal text-lowercase" style="letter-spacing:0"><?= (int)$r['usuarios'] ?> usuario<?= (int)$r['usuarios'] === 1 ? '' : 's' ?></div></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($permisos as $modulo => $lista): ?>
      <tr><td colspan="<?= count($roles) + 1 ?>" style="background:var(--c-bruma)"><span class="c-eyebrow"><?= e($modulo) ?></span></td></tr>
      <?php foreach ($lista as $p): ?>
        <tr><td><?= e($p['descripcion']) ?></td>
          <?php foreach ($roles as $r): ?>
            <td class="text-center"><input class="form-check-input" type="checkbox" style="width:1.2em;height:1.2em"
              <?= $r['es_sistema'] ? 'checked disabled' : 'name="permisos[' . $r['id'] . '][]" value="' . $p['id'] . '" ' . checked(!empty($asignados[(int)$r['id']][(int)$p['id']])) ?>
              aria-label="<?= e($r['nombre'] . ': ' . $p['descripcion']) ?>"></td>
          <?php endforeach; ?></tr>
      <?php endforeach; ?>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <div class="c-card__body text-end"><button class="btn btn-primary"><i class="bi bi-check2"></i> Guardar permisos</button></div>
</form>
<?php endif; ?>
