<?php $v = fn($k, $d = '') => old($k, $s[$k] ?? $d); ?>
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= url('sucursales') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
  <h1 class="h4 mb-0"><?= e($title) ?></h1>
</div>
<form method="post" action="<?= url('sucursales/guardar') ?>" class="card card-body" style="max-width: 760px">
  <?= csrf_field() ?>
  <?php if ($s): ?><input type="hidden" name="id" value="<?= $s['id'] ?>"><?php endif; ?>
  <div class="row g-3">
    <div class="col-4 col-md-3"><label class="form-label">Código *</label><input name="codigo" class="form-control text-uppercase" required maxlength="10" value="<?= e($v('codigo')) ?>"></div>
    <div class="col-8 col-md-9"><label class="form-label">Nombre *</label><input name="nombre" class="form-control" required maxlength="100" value="<?= e($v('nombre')) ?>"></div>
    <div class="col-md-8"><label class="form-label">Dirección</label><input name="direccion" class="form-control" maxlength="255" value="<?= e($v('direccion')) ?>"></div>
    <div class="col-md-4"><label class="form-label">Teléfono</label><input name="telefono" class="form-control" maxlength="30" value="<?= e($v('telefono')) ?>"></div>
    <div class="col-12"><div class="form-check form-switch"><input type="hidden" name="activo" value="0">
      <input class="form-check-input" type="checkbox" name="activo" value="1" id="act" <?= checked((bool)$v('activo', 1)) ?>><label class="form-check-label" for="act">Sucursal activa</label></div></div>
  </div>
  <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button></div>
</form>
