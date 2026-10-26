<?php $v = fn($k, $d = '') => old($k, $p[$k] ?? $d); ?>
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= $p ? url('productos/ver', ['id' => $p['id']]) : url('productos') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
  <h1 class="h4 mb-0"><?= e($title) ?></h1>
</div>
<form method="post" action="<?= url('productos/guardar') ?>" enctype="multipart/form-data" class="card card-body">
  <?= csrf_field() ?>
  <?php if ($p): ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><?php endif; ?>
  <div class="row g-3">
    <div class="col-md-3"><label class="form-label">Código *</label>
      <input name="codigo" class="form-control text-uppercase" required maxlength="40" value="<?= e($v('codigo')) ?>" placeholder="PB-TOY-COR-14"></div>
    <div class="col-md-6"><label class="form-label">Nombre *</label>
      <input name="nombre" class="form-control" required maxlength="150" value="<?= e($v('nombre')) ?>" placeholder="Parabrisas Toyota Corolla 2014-2019"></div>
    <div class="col-md-3"><label class="form-label">Tipo *</label>
      <select name="tipo_id" class="form-select" required><option value="">Seleccione…</option>
        <?php foreach ($tipos as $t): ?><option value="<?= $t['id'] ?>" <?= selected($t['id'], $v('tipo_id')) ?>><?= e($t['nombre']) ?></option><?php endforeach; ?></select></div>

    <div class="col-md-3"><label class="form-label">Marca de vehículo</label>
      <input name="marca_vehiculo" class="form-control" list="marcas" maxlength="60" value="<?= e($v('marca_vehiculo')) ?>">
      <datalist id="marcas"><?php foreach ($marcas as $m): ?><option value="<?= e($m) ?>"><?php endforeach; ?></datalist></div>
    <div class="col-md-3"><label class="form-label">Modelo</label>
      <input name="modelo_vehiculo" class="form-control" maxlength="80" value="<?= e($v('modelo_vehiculo')) ?>"></div>
    <div class="col-6 col-md-3"><label class="form-label">Año desde</label>
      <input name="anio_desde" type="number" min="1950" max="<?= date('Y') + 2 ?>" class="form-control" value="<?= e($v('anio_desde')) ?>"></div>
    <div class="col-6 col-md-3"><label class="form-label">Año hasta</label>
      <input name="anio_hasta" type="number" min="1950" max="<?= date('Y') + 2 ?>" class="form-control" value="<?= e($v('anio_hasta')) ?>"></div>

    <div class="col-6 col-md-3"><label class="form-label">Precio de venta *</label>
      <input name="precio" type="number" step="0.01" min="0.01" class="form-control" required value="<?= e($v('precio')) ?>"></div>
    <div class="col-6 col-md-3"><label class="form-label">Costo</label>
      <input name="costo" type="number" step="0.01" min="0" class="form-control" value="<?= e($v('costo', '0')) ?>"></div>
    <div class="col-6 col-md-3"><label class="form-label">Stock mínimo</label>
      <input name="stock_minimo" type="number" min="0" class="form-control" value="<?= e($v('stock_minimo', '0')) ?>">
      <div class="form-text">Se alerta al llegar a este nivel.</div></div>
    <div class="col-6 col-md-3"><label class="form-label">Imagen (JPG/PNG/WEBP, 2 MB)</label>
      <input name="imagen" type="file" accept="image/jpeg,image/png,image/webp" class="form-control">
      <?php if (!empty($p['imagen'])): ?><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="quitar_imagen" value="1" id="qi"><label class="form-check-label small" for="qi">Quitar imagen actual</label></div><?php endif; ?></div>

    <div class="col-12"><label class="form-label">Descripción</label>
      <textarea name="descripcion" rows="3" class="form-control"><?= e($v('descripcion')) ?></textarea></div>

    <?php if (!$p && can('inventario.movimientos')): ?>
    <div class="col-12"><div class="border rounded p-3 bg-body-tertiary">
      <div class="fw-semibold mb-2">Existencia inicial (opcional)</div>
      <div class="row g-2">
        <?php foreach ($sucursales as $s): ?>
          <div class="col-6 col-md-3"><label class="form-label small"><?= e($s['nombre']) ?></label>
            <input type="number" min="0" name="inicial[<?= $s['id'] ?>]" class="form-control form-control-sm" value="<?= e(((array)old('inicial', []))[$s['id']] ?? '') ?>"></div>
        <?php endforeach; ?>
      </div>
      <div class="form-text">Se registrará como movimiento de ENTRADA “Inventario inicial”.</div>
    </div></div>
    <?php endif; ?>
  </div>
  <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button></div>
</form>
