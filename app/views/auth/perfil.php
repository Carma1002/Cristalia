<?= ui_header('Mi cuenta', 'Sus datos de acceso y el cambio de contraseña.') ?>
<div class="row g-3 c-stagger">
  <div class="col-lg-5">
    <div class="c-card c-card__body h-100">
      <div class="d-flex align-items-center gap-3 mb-4">
        <span class="c-avatar c-avatar--lg"><?= e(iniciales($u['nombre'])) ?></span>
        <div><div class="c-display fs-5"><?= e($u['nombre']) ?></div><div class="text-muted small">@<?= e($u['usuario']) ?></div></div>
      </div>
      <dl class="c-dl">
        <dt>Rol</dt><dd><?= e($u['rol_nombre']) ?></dd>
        <dt>Sucursal</dt><dd><?= e($u['sucursal_nombre'] ?? 'Todas') ?></dd>
        <dt>Correo</dt><dd><?= e($u['email'] ?: '—') ?></dd>
        <dt>Último acceso</dt><dd><?= fecha($u['ultimo_acceso']) ?></dd>
      </dl>
    </div>
  </div>
  <div class="col-lg-7">
    <form method="post" action="<?= url('perfil') ?>" class="c-card c-card__body h-100">
      <?= csrf_field() ?>
      <h2 class="c-section-title"><i class="bi bi-shield-lock" style="color:var(--c-cian)"></i> Cambiar contraseña</h2>
      <div class="mb-3"><label class="form-label">Contraseña actual</label>
        <div class="c-pw"><input type="password" name="actual" class="form-control" required><button type="button" data-toggle-pw aria-label="Mostrar"><i class="bi bi-eye"></i></button></div></div>
      <div class="row g-3 mb-2">
        <div class="col-sm-6"><label class="form-label">Nueva contraseña</label><input type="password" name="nueva" class="form-control" required minlength="8"></div>
        <div class="col-sm-6"><label class="form-label">Repetir la nueva</label><input type="password" name="confirmar" class="form-control" required></div>
      </div>
      <p class="form-text mb-3">Mínimo 8 caracteres, con letras y números.</p>
      <div><button class="btn btn-primary"><i class="bi bi-check2"></i> Guardar contraseña</button></div>
    </form>
  </div>
</div>
