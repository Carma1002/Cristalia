<?php $logo = logo_cliente(); ?>
<?= ui_header('Empresa y sucursales', 'Datos que aparecen en los comprobantes y reportes, sucursales y tipos de producto.') ?>

<div class="row g-3 c-stagger">
  <?php if (can('admin.configuracion')): ?>
  <div class="col-xl-5">
    <form method="post" action="<?= url('configuracion/guardar') ?>" enctype="multipart/form-data" class="c-card c-card__body h-100">
      <?= csrf_field() ?>
      <h2 class="c-section-title"><i class="bi bi-building" style="color:var(--c-cian)"></i> Datos de la empresa</h2>
      <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-4" style="background:var(--c-bruma)">
        <label for="logo" style="cursor:pointer" title="Cambiar logo">
          <img id="logoPrev" src="<?= e($logo ?? 'assets/img/cristalia-isotipo.svg') ?>" alt="Logo" style="width:72px;height:72px;border-radius:50%;object-fit:cover;background:#fff;box-shadow:0 6px 16px -8px rgba(0,0,0,.4)">
        </label>
        <div class="min-w-0"><div class="fw-semibold">Logo de la empresa</div><small class="text-muted d-block mb-2">Aparece al iniciar sesión, en los comprobantes y en los PDF.</small>
          <input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/webp" class="form-control form-control-sm"></div>
      </div>
      <?php if (ajuste('empresa_logo') !== ''): ?><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="quitar_logo" value="1" id="ql"><label class="form-check-label small" for="ql">Volver al logo predeterminado</label></div><?php endif; ?>
      <div class="mb-3"><label class="form-label" for="empresa_nombre">Razón social</label><input id="empresa_nombre" name="empresa_nombre" class="form-control" required value="<?= e(ajuste('empresa_nombre')) ?>"></div>
      <div class="row g-3 mb-3">
        <div class="col-8"><label class="form-label" for="empresa_rif">RIF</label><input id="empresa_rif" name="empresa_rif" class="form-control" required value="<?= e(ajuste('empresa_rif')) ?>"></div>
        <div class="col-4"><label class="form-label" for="moneda">Moneda</label><input id="moneda" name="moneda" class="form-control" required maxlength="5" value="<?= e(ajuste('moneda')) ?>"></div>
      </div>
      <div class="mb-4"><label class="form-label" for="empresa_direccion">Dirección</label><input id="empresa_direccion" name="empresa_direccion" class="form-control" value="<?= e(ajuste('empresa_direccion')) ?>"></div>
      <button class="btn btn-primary"><i class="bi bi-check2"></i> Guardar datos</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="col-xl-7 d-flex flex-column gap-3">
    <?php if (can('admin.sucursales')): ?>
    <div class="c-card">
      <div class="c-card__head"><div><h2 class="c-card__title">Sucursales</h2><p class="c-card__sub">Cada sucursal tiene sus propias existencias, pedidos y usuarios.</p></div>
        <button class="btn btn-soft btn-sm" data-bs-toggle="modal" data-bs-target="#mSucursal" data-suc='{}'><i class="bi bi-plus-lg"></i> Nueva</button></div>
      <ul class="c-list mt-2">
        <?php foreach ($sucursales as $s): ?>
          <li><div class="<?= $s['activo'] ? '' : 'opacity-50' ?>">
            <span class="c-list__ico"><?= e($s['codigo']) ?></span>
            <span class="c-list__main"><span class="c-list__title d-block"><?= e($s['nombre']) ?> <?= $s['activo'] ? '' : '<span class="c-pill c-pill--muted">Inactiva</span>' ?></span>
              <span class="c-list__sub d-block"><?= (int)$s['usuarios'] ?> usuarios · <?= num($s['unidades']) ?> unidades · <?= (int)$s['pedidos_abiertos'] ?> pedidos abiertos · <?= money($s['ventas_mes']) ?> este mes</span></span>
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#mSucursal" data-suc="<?= e(json_encode(['id' => $s['id'], 'codigo' => $s['codigo'], 'nombre' => $s['nombre'], 'direccion' => $s['direccion'], 'telefono' => $s['telefono'], 'activo' => (int)$s['activo']])) ?>"><i class="bi bi-pencil"></i></button>
          </div></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if (can('admin.configuracion')): ?>
    <div class="c-card">
      <div class="c-card__head"><div><h2 class="c-card__title">Tipos de producto</h2><p class="c-card__sub">Sirven para clasificar y filtrar el catálogo.</p></div></div>
      <div class="c-card__body d-flex flex-column gap-2">
        <?php foreach ($tipos as $t): ?>
          <form method="post" action="<?= url('tipos/guardar') ?>" class="d-flex gap-2 align-items-center" <?= !$t['productos'] ? '' : '' ?>><?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $t['id'] ?>">
            <input name="nombre" class="form-control form-control-sm" value="<?= e($t['nombre']) ?>" aria-label="Nombre del tipo">
            <span class="c-pill c-pill--muted text-nowrap"><?= (int)$t['productos'] ?> prod.</span>
            <button class="btn btn-light btn-sm" title="Guardar"><i class="bi bi-check2"></i></button>
            <?php if (!$t['productos']): ?><button class="btn btn-light btn-sm" name="eliminar" value="1" title="Eliminar"><i class="bi bi-trash3"></i></button><?php endif; ?>
          </form>
        <?php endforeach; ?>
        <form method="post" action="<?= url('tipos/guardar') ?>" class="d-flex gap-2 mt-2"><?= csrf_field() ?>
          <input name="nombre" class="form-control form-control-sm" placeholder="Nuevo tipo de producto" required>
          <button class="btn btn-soft btn-sm text-nowrap"><i class="bi bi-plus-lg"></i> Agregar</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if (can('admin.sucursales')): ?>
<div class="modal fade" id="mSucursal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="<?= url('sucursales/guardar') ?>" class="modal-content">
      <?= csrf_field() ?><input type="hidden" name="id">
      <div class="modal-header"><h2 class="modal-title" data-titulo>Sucursal</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-4"><label class="form-label">Código</label><input name="codigo" class="form-control text-uppercase" required maxlength="10" placeholder="CCS"></div>
          <div class="col-8"><label class="form-label">Nombre</label><input name="nombre" class="form-control" required maxlength="100"></div>
          <div class="col-12"><label class="form-label">Dirección</label><input name="direccion" class="form-control" maxlength="255"></div>
          <div class="col-6"><label class="form-label">Teléfono</label><input name="telefono" class="form-control" maxlength="30"></div>
          <div class="col-6 d-flex align-items-end"><div class="form-check form-switch mb-2"><input type="hidden" name="activo" value="0"><input class="form-check-input" type="checkbox" role="switch" name="activo" value="1" id="sucAct" checked><label class="form-check-label" for="sucAct">Activa</label></div></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary"><i class="bi bi-check2"></i> Guardar sucursal</button></div>
    </form>
  </div>
</div>
<script>
document.getElementById('mSucursal').addEventListener('show.bs.modal', e => {
  const d = JSON.parse(e.relatedTarget?.dataset.suc || '{}'), f = e.target.querySelector('form');
  ['id', 'codigo', 'nombre', 'direccion', 'telefono'].forEach(k => f.querySelector('[name=' + k + ']').value = d[k] ?? '');
  f.querySelector('#sucAct').checked = d.activo === undefined ? true : !!d.activo;
  e.target.querySelector('[data-titulo]').textContent = d.id ? 'Editar sucursal' : 'Nueva sucursal';
});
</script>
<?php endif; ?>
<?php if (can('admin.configuracion')): ?>
<script>
document.getElementById('logo').addEventListener('change', e => { const f = e.target.files[0]; if (f) document.getElementById('logoPrev').src = URL.createObjectURL(f); });
</script>
<?php endif; ?>
