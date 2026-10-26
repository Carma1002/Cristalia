<?php $v = fn($k, $d = '') => old($k, $u[$k] ?? $d); ?>
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= url('usuarios') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
  <h1 class="h4 mb-0"><?= e($title) ?></h1>
</div>
<form method="post" action="<?= url('usuarios/guardar') ?>" class="card card-body" style="max-width: 760px" autocomplete="off">
  <?= csrf_field() ?>
  <?php if ($u): ?><input type="hidden" name="id" value="<?= $u['id'] ?>"><?php endif; ?>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Nombre completo *</label><input name="nombre" class="form-control" required value="<?= e($v('nombre')) ?>"></div>
    <div class="col-md-6"><label class="form-label">Usuario *</label><input name="usuario" class="form-control" required pattern="[a-z0-9._\-]{3,40}" value="<?= e($v('usuario')) ?>" autocapitalize="none">
      <div class="form-text">Minúsculas, números, punto, guion.</div></div>
    <div class="col-md-6"><label class="form-label">Correo</label><input name="email" type="email" class="form-control" value="<?= e($v('email')) ?>"></div>
    <div class="col-md-6"><label class="form-label">Rol *</label>
      <select name="rol_id" class="form-select" required>
        <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>" <?= selected($r['id'], $v('rol_id')) ?>><?= e($r['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label">Sucursal</label>
      <select name="sucursal_id" class="form-select"><option value="">Todas (solo administradores)</option>
        <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $v('sucursal_id')) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select>
      <div class="form-text">Los usuarios no administradores solo operan en su sucursal.</div></div>
    <div class="col-md-6"></div>
    <div class="col-md-6"><label class="form-label">Contraseña <?= $u ? '(dejar vacío para no cambiar)' : '*' ?></label>
      <input type="password" name="password" class="form-control" <?= $u ? '' : 'required' ?> minlength="8" autocomplete="new-password"></div>
    <div class="col-md-6"><label class="form-label">Confirmar contraseña</label><input type="password" name="password2" class="form-control" autocomplete="new-password"></div>
  </div>
  <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button></div>
</form>
