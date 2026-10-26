<?php $v = fn($k, $d = '') => old($k, $c[$k] ?? $d); ?>
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= $c ? url('clientes/ver', ['id' => $c['id']]) : url($volver === 'pedido' ? 'pedidos/crear' : 'clientes') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
  <h1 class="h4 mb-0"><?= e($title) ?></h1>
</div>
<form method="post" action="<?= url('clientes/guardar') ?>" class="card card-body" style="max-width: 760px">
  <?= csrf_field() ?>
  <?php if ($c): ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><?php endif; ?>
  <input type="hidden" name="volver" value="<?= e($volver) ?>">
  <div class="row g-3">
    <div class="col-4 col-md-3"><label class="form-label">Tipo *</label>
      <select name="tipo_documento" class="form-select"><?php foreach (Cliente::TIPOS_DOC as $k => $t): ?><option value="<?= $k ?>" <?= selected($k, $v('tipo_documento', 'V')) ?>><?= $t ?></option><?php endforeach; ?></select></div>
    <div class="col-8 col-md-4"><label class="form-label">Cédula / RIF *</label>
      <input name="documento" class="form-control" required maxlength="15" value="<?= e($v('documento')) ?>" inputmode="numeric"></div>
    <div class="col-md-5"><label class="form-label">Nombre o razón social *</label>
      <input name="nombre" class="form-control" required maxlength="150" value="<?= e($v('nombre')) ?>"></div>
    <div class="col-md-4"><label class="form-label">Teléfono</label>
      <input name="telefono" class="form-control" maxlength="30" value="<?= e($v('telefono')) ?>" inputmode="tel"></div>
    <div class="col-md-8"><label class="form-label">Correo</label>
      <input name="email" type="email" class="form-control" maxlength="120" value="<?= e($v('email')) ?>"></div>
    <div class="col-12"><label class="form-label">Dirección</label>
      <input name="direccion" class="form-control" maxlength="255" value="<?= e($v('direccion')) ?>"></div>
    <?php if ($c): ?>
    <div class="col-12"><div class="form-check form-switch"><input type="hidden" name="activo" value="0">
      <input class="form-check-input" type="checkbox" name="activo" value="1" id="act" <?= checked((bool)$v('activo', 1)) ?>><label class="form-check-label" for="act">Cliente activo</label></div></div>
    <?php endif; ?>
  </div>
  <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button></div>
</form>
