<h1 class="h4 mb-3">Configuración</h1>
<div class="row g-3">
  <div class="col-lg-6">
    <form method="post" action="<?= url('configuracion/guardar') ?>" class="card card-body">
      <?= csrf_field() ?>
      <h2 class="h6">Datos de la empresa (encabezado de comprobantes y reportes)</h2>
      <div class="mb-2"><label class="form-label">Razón social</label><input name="empresa_nombre" class="form-control" required value="<?= e(ajuste('empresa_nombre')) ?>"></div>
      <div class="row g-2 mb-2">
        <div class="col-8"><label class="form-label">RIF</label><input name="empresa_rif" class="form-control" required value="<?= e(ajuste('empresa_rif')) ?>"></div>
        <div class="col-4"><label class="form-label">Símbolo moneda</label><input name="moneda" class="form-control" required maxlength="5" value="<?= e(ajuste('moneda')) ?>"></div>
      </div>
      <div class="mb-3"><label class="form-label">Dirección</label><input name="empresa_direccion" class="form-control" required value="<?= e(ajuste('empresa_direccion')) ?>"></div>
      <div><button class="btn btn-primary">Guardar</button></div>
    </form>
  </div>
  <div class="col-lg-6">
    <div class="card"><div class="card-header">Tipos de producto</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($tipos as $t): ?>
        <li class="list-group-item">
          <form method="post" action="<?= url('tipos/guardar') ?>" class="d-flex gap-2 align-items-center"><?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $t['id'] ?>">
            <input name="nombre" class="form-control form-control-sm" value="<?= e($t['nombre']) ?>">
            <span class="badge bg-secondary"><?= (int)$t['productos'] ?></span>
            <button class="btn btn-sm btn-outline-primary" title="Guardar"><i class="bi bi-check"></i></button>
            <?php if (!$t['productos']): ?><button class="btn btn-sm btn-outline-danger" name="eliminar" value="1" title="Eliminar" onclick="return confirm('¿Eliminar tipo?')"><i class="bi bi-trash"></i></button><?php endif; ?>
          </form>
        </li>
        <?php endforeach; ?>
        <li class="list-group-item">
          <form method="post" action="<?= url('tipos/guardar') ?>" class="d-flex gap-2"><?= csrf_field() ?>
            <input name="nombre" class="form-control form-control-sm" placeholder="Nuevo tipo de producto" required>
            <button class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-plus"></i> Agregar</button>
          </form>
        </li>
      </ul>
    </div>
  </div>
</div>
