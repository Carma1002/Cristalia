<?php $v = fn($k, $d = '') => old($k, $u[$k] ?? $d); ?>
<?= ui_header($title, $u ? '@' . e($u['usuario']) : 'Cree el acceso de una persona y asígnele un rol.', '', url('usuarios')) ?>

<form method="post" action="<?= url('usuarios/guardar') ?>" class="c-card c-card__body reveal" style="max-width:860px;--d:1" autocomplete="off">
  <?= csrf_field() ?>
  <?php if ($u): ?><input type="hidden" name="id" value="<?= $u['id'] ?>"><?php endif; ?>
  <h2 class="c-section-title"><span class="num-step">1</span> Datos de la persona</h2>
  <div class="row g-3 mb-4">
    <div class="col-md-6"><label class="form-label" for="nombre">Nombre completo *</label><input id="nombre" name="nombre" class="form-control" required value="<?= e($v('nombre')) ?>"></div>
    <div class="col-md-6"><label class="form-label" for="email">Correo</label><input id="email" name="email" type="email" class="form-control" value="<?= e($v('email')) ?>"></div>
  </div>
  <h2 class="c-section-title"><span class="num-step">2</span> Rol y sucursal</h2>
  <div class="c-tiles mb-3" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
    <?php $desc = [1 => ['shield-lock', 'Acceso total al sistema'], 2 => ['receipt', 'Clientes, pedidos y ventas'], 3 => ['box-seam', 'Productos y existencias']];
    foreach ($roles as $r): [$ico, $d] = $desc[$r['id']] ?? ['person', $r['descripcion'] ?? '']; ?>
      <div class="c-tile"><input type="radio" name="rol_id" id="rol<?= $r['id'] ?>" value="<?= $r['id'] ?>" <?= checked((string)$v('rol_id', 2) === (string)$r['id']) ?> required>
        <label for="rol<?= $r['id'] ?>"><i class="bi bi-<?= $ico ?>"></i><?= e($r['nombre']) ?><small><?= e($d) ?></small></label></div>
    <?php endforeach; ?>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-md-6"><label class="form-label" for="sucursal_id">Sucursal</label>
      <select id="sucursal_id" name="sucursal_id" class="form-select"><option value="">Todas (sólo administradores)</option>
        <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $v('sucursal_id')) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select>
      <div class="form-text">Ventas y Almacén sólo trabajan con su sucursal.</div></div>
  </div>
  <h2 class="c-section-title"><span class="num-step">3</span> Acceso</h2>
  <div class="row g-3">
    <div class="col-md-4"><label class="form-label" for="usuario">Usuario *</label><input id="usuario" name="usuario" class="form-control" required pattern="[a-z0-9._\-]{3,40}" value="<?= e($v('usuario')) ?>" autocapitalize="none" placeholder="maria.rodriguez">
      <div class="form-text">Minúsculas, números, punto o guion.</div></div>
    <div class="col-md-4"><label class="form-label" for="password">Contraseña <?= $u ? '<small class="text-muted fw-normal">(vacío = no cambiar)</small>' : '*' ?></label>
      <div class="c-pw"><input id="password" type="password" name="password" class="form-control" <?= $u ? '' : 'required' ?> minlength="8" autocomplete="new-password"><button type="button" data-toggle-pw aria-label="Mostrar"><i class="bi bi-eye"></i></button></div></div>
    <div class="col-md-4"><label class="form-label" for="password2">Repetir contraseña</label><input id="password2" type="password" name="password2" class="form-control" autocomplete="new-password"></div>
  </div>
  <p class="form-text">La contraseña debe tener al menos 8 caracteres, con letras y números.</p>
  <div class="d-flex gap-2 mt-3"><button class="btn btn-primary"><i class="bi bi-check2"></i> Guardar usuario</button><a href="<?= url('usuarios') ?>" class="btn btn-light">Cancelar</a></div>
</form>
