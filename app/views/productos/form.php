<?php $v = fn($k, $d = '') => old($k, $p[$k] ?? $d); ?>
<?= ui_header($title, $p ? e($p['codigo']) . ' · los cambios quedan registrados en la bitácora' : 'Complete los datos. Los campos con * son obligatorios.', '', $p ? url('productos/ver', ['id' => $p['id']]) : url('productos')) ?>

<form method="post" action="<?= url('productos/guardar') ?>" enctype="multipart/form-data" class="row g-3 c-stagger">
  <?= csrf_field() ?>
  <?php if ($p): ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><?php endif; ?>
  <div class="col-lg-8 d-flex flex-column gap-3">
    <section class="c-card c-card__body">
      <h2 class="c-section-title"><span class="num-step">1</span> Identificación</h2>
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="codigo">Código *</label>
          <input id="codigo" name="codigo" class="form-control text-uppercase" required maxlength="40" value="<?= e($v('codigo')) ?>" placeholder="PB-TOY-COR-14"></div>
        <div class="col-md-8"><label class="form-label" for="nombre">Nombre *</label>
          <input id="nombre" name="nombre" class="form-control" required maxlength="150" value="<?= e($v('nombre')) ?>" placeholder="Parabrisas Toyota Corolla 2014-2019"></div>
        <div class="col-md-6"><label class="form-label" for="tipo_id">Tipo de producto *</label>
          <select id="tipo_id" name="tipo_id" class="form-select" required><option value="">Seleccione…</option>
            <?php foreach ($tipos as $t): ?><option value="<?= $t['id'] ?>" <?= selected($t['id'], $v('tipo_id')) ?>><?= e($t['nombre']) ?></option><?php endforeach; ?></select></div>
      </div>
    </section>

    <section class="c-card c-card__body">
      <h2 class="c-section-title"><span class="num-step">2</span> Vehículo compatible <small class="text-muted fw-normal">(opcional)</small></h2>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="marca_vehiculo">Marca</label>
          <input id="marca_vehiculo" name="marca_vehiculo" class="form-control" list="marcas" maxlength="60" value="<?= e($v('marca_vehiculo')) ?>" placeholder="Toyota">
          <datalist id="marcas"><?php foreach ($marcas as $m): ?><option value="<?= e($m) ?>"><?php endforeach; ?></datalist></div>
        <div class="col-md-6"><label class="form-label" for="modelo_vehiculo">Modelo</label>
          <input id="modelo_vehiculo" name="modelo_vehiculo" class="form-control" maxlength="80" value="<?= e($v('modelo_vehiculo')) ?>" placeholder="Corolla"></div>
        <div class="col-6 col-md-3"><label class="form-label" for="anio_desde">Desde el año</label>
          <input id="anio_desde" name="anio_desde" type="number" min="1950" max="<?= date('Y') + 2 ?>" class="form-control" value="<?= e($v('anio_desde')) ?>"></div>
        <div class="col-6 col-md-3"><label class="form-label" for="anio_hasta">Hasta el año</label>
          <input id="anio_hasta" name="anio_hasta" type="number" min="1950" max="<?= date('Y') + 2 ?>" class="form-control" value="<?= e($v('anio_hasta')) ?>"></div>
      </div>
    </section>

    <section class="c-card c-card__body">
      <h2 class="c-section-title"><span class="num-step">3</span> Precio y control de stock</h2>
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="precio">Precio de venta *</label>
          <div class="input-group"><span class="input-group-text"><?= e(ajuste('moneda', '$')) ?></span><input id="precio" name="precio" type="number" step="0.01" min="0.01" class="form-control" required value="<?= e($v('precio')) ?>"></div></div>
        <div class="col-md-4"><label class="form-label" for="costo">Costo</label>
          <div class="input-group"><span class="input-group-text"><?= e(ajuste('moneda', '$')) ?></span><input id="costo" name="costo" type="number" step="0.01" min="0" class="form-control" value="<?= e($v('costo', '0')) ?>"></div></div>
        <div class="col-md-4"><label class="form-label" for="stock_minimo">Stock mínimo</label>
          <input id="stock_minimo" name="stock_minimo" type="number" min="0" class="form-control" value="<?= e($v('stock_minimo', '0')) ?>">
          <div class="form-text">Al llegar a esta cantidad, el sistema avisa.</div></div>
        <div class="col-12"><label class="form-label" for="descripcion">Descripción</label>
          <textarea id="descripcion" name="descripcion" rows="3" class="form-control" placeholder="Características, material, incluye moldura…"><?= e($v('descripcion')) ?></textarea></div>
      </div>
    </section>

    <?php if (!$p && can('inventario.movimientos')): ?>
    <section class="c-card c-card__body">
      <h2 class="c-section-title"><span class="num-step">4</span> Existencia inicial <small class="text-muted fw-normal">(opcional)</small></h2>
      <p class="small text-muted">¿Cuántas unidades hay hoy en cada sucursal? Se registran como una entrada de “Inventario inicial”.</p>
      <div class="row g-3">
        <?php foreach ($sucursales as $s): ?>
          <div class="col-6 col-md-4"><label class="form-label small"><?= e($s['nombre']) ?></label>
            <input type="number" min="0" name="inicial[<?= $s['id'] ?>]" class="form-control" placeholder="0" value="<?= e(((array)old('inicial', []))[$s['id']] ?? '') ?>"></div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <div class="c-card c-card__body c-sticky">
      <h2 class="c-section-title"><i class="bi bi-image" style="color:var(--c-cian)"></i> Foto del producto</h2>
      <label for="imagen" class="c-product__img d-block rounded-4 mb-3" style="cursor:pointer;aspect-ratio:4/3">
        <img id="previewImg" src="<?= !empty($p['imagen']) ? e($p['imagen']) : '' ?>" alt="" class="<?= empty($p['imagen']) ? 'd-none' : '' ?>">
        <span class="c-ph flex-column gap-2 text-muted small <?= !empty($p['imagen']) ? 'd-none' : '' ?>" id="previewPh"><i class="bi bi-cloud-arrow-up fs-2" style="color:var(--c-cian)"></i>Toque para elegir una foto</span>
      </label>
      <input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm">
      <div class="form-text">JPG, PNG o WEBP de hasta 2 MB.</div>
      <?php if (!empty($p['imagen'])): ?><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="quitar_imagen" value="1" id="qi"><label class="form-check-label small" for="qi">Quitar la foto actual</label></div><?php endif; ?>
      <hr class="my-4">
      <button class="btn btn-primary btn-lg w-100"><i class="bi bi-check2"></i> <?= $p ? 'Guardar cambios' : 'Crear producto' ?></button>
      <a href="<?= $p ? url('productos/ver', ['id' => $p['id']]) : url('productos') ?>" class="btn btn-light w-100 mt-2">Cancelar</a>
    </div>
  </div>
</form>
<script>
document.getElementById('imagen').addEventListener('change', e => {
  const f = e.target.files[0]; if (!f) return;
  const img = document.getElementById('previewImg'); img.src = URL.createObjectURL(f);
  img.classList.remove('d-none'); document.getElementById('previewPh').classList.add('d-none');
});
</script>
