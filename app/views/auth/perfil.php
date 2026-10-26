<h1 class="h4 mb-3">Mi perfil</h1>
<div class="row g-3">
  <div class="col-md-5">
    <div class="card"><div class="card-body">
      <dl class="mb-0">
        <dt>Nombre</dt><dd><?= e($u['nombre']) ?></dd>
        <dt>Usuario</dt><dd><?= e($u['usuario']) ?></dd>
        <dt>Rol</dt><dd><?= e($u['rol_nombre']) ?></dd>
        <dt>Sucursal</dt><dd><?= e($u['sucursal_nombre'] ?? 'Todas') ?></dd>
        <dt>Último acceso</dt><dd class="mb-0"><?= fecha($u['ultimo_acceso']) ?></dd>
      </dl>
    </div></div>
  </div>
  <div class="col-md-7">
    <div class="card"><div class="card-header">Cambiar contraseña</div><div class="card-body">
      <form method="post" action="<?= url('perfil') ?>">
        <?= csrf_field() ?>
        <div class="mb-2"><label class="form-label">Contraseña actual</label><input type="password" name="actual" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Nueva contraseña</label><input type="password" name="nueva" class="form-control" required minlength="8"></div>
        <div class="mb-3"><label class="form-label">Confirmar</label><input type="password" name="confirmar" class="form-control" required></div>
        <div class="form-text mb-3">Mínimo 8 caracteres con letras y números.</div>
        <button class="btn btn-primary">Guardar</button>
      </form>
    </div></div>
  </div>
</div>
