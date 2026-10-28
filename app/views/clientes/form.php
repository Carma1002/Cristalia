<?php $v = fn($k, $d = '') => old($k, $c[$k] ?? $d); ?>
<?= ui_header($title, $volver === 'pedido' ? 'Al guardar volverá al pedido con este cliente seleccionado.' : 'Los campos con * son obligatorios.', '', $c ? url('clientes/ver', ['id' => $c['id']]) : url($volver === 'pedido' ? 'pedidos/crear' : 'clientes')) ?>

<form method="post" action="<?= url('clientes/guardar') ?>" class="c-card c-card__body reveal" style="max-width:820px;--d:1">
  <?= csrf_field() ?>
  <?php if ($c): ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><?php endif; ?>
  <input type="hidden" name="volver" value="<?= e($volver) ?>">
  <h2 class="c-section-title"><span class="num-step">1</span> Identificación</h2>
  <div class="row g-3 mb-4">
    <div class="col-5 col-md-3"><label class="form-label" for="tipo_documento">Tipo *</label>
      <select id="tipo_documento" name="tipo_documento" class="form-select"><?php foreach (Cliente::TIPOS_DOC as $k => $t): ?><option value="<?= $k ?>" <?= selected($k, $v('tipo_documento', 'V')) ?>><?= $t ?></option><?php endforeach; ?></select></div>
    <div class="col-7 col-md-4"><label class="form-label" for="documento">Cédula o RIF *</label>
      <input id="documento" name="documento" class="form-control" required maxlength="15" value="<?= e($v('documento')) ?>" inputmode="numeric" placeholder="12345678"></div>
    <div class="col-md-5"><label class="form-label" for="nombre">Nombre o razón social *</label>
      <input id="nombre" name="nombre" class="form-control" required maxlength="150" value="<?= e($v('nombre')) ?>"></div>
  </div>
  <h2 class="c-section-title"><span class="num-step">2</span> Contacto <small class="text-muted fw-normal">(opcional)</small></h2>
  <div class="row g-3">
    <div class="col-md-5"><label class="form-label" for="telefono">Teléfono</label>
      <input id="telefono" name="telefono" class="form-control" maxlength="30" value="<?= e($v('telefono')) ?>" inputmode="tel" placeholder="0414-000-0000"></div>
    <div class="col-md-7"><label class="form-label" for="email">Correo</label>
      <input id="email" name="email" type="email" class="form-control" maxlength="120" value="<?= e($v('email')) ?>" placeholder="nombre@correo.com"></div>
    <div class="col-12"><label class="form-label" for="direccion">Dirección</label>
      <input id="direccion" name="direccion" class="form-control" maxlength="255" value="<?= e($v('direccion')) ?>"></div>
    <?php if ($c): ?>
    <div class="col-12"><div class="form-check form-switch"><input type="hidden" name="activo" value="0">
      <input class="form-check-input" type="checkbox" role="switch" name="activo" value="1" id="act" <?= checked((bool)$v('activo', 1)) ?>><label class="form-check-label" for="act">Cliente activo (los inactivos no aparecen al crear pedidos)</label></div></div>
    <?php endif; ?>
  </div>
  <div class="d-flex gap-2 mt-4"><button class="btn btn-primary"><i class="bi bi-check2"></i> Guardar cliente</button>
    <a href="<?= $c ? url('clientes/ver', ['id' => $c['id']]) : url($volver === 'pedido' ? 'pedidos/crear' : 'clientes') ?>" class="btn btn-light">Cancelar</a></div>
</form>
