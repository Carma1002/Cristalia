<?php $sistema = $rol && $rol['es_sistema']; ?>
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= url('roles') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
  <h1 class="h4 mb-0"><?= e($title) ?></h1>
</div>
<form method="post" action="<?= url('roles/guardar') ?>" class="card card-body">
  <?= csrf_field() ?>
  <?php if ($rol): ?><input type="hidden" name="id" value="<?= $rol['id'] ?>"><?php endif; ?>
  <div class="row g-3 mb-3">
    <div class="col-md-4"><label class="form-label">Nombre *</label><input name="nombre" class="form-control" required value="<?= e(old('nombre', $rol['nombre'] ?? '')) ?>"></div>
    <div class="col-md-8"><label class="form-label">Descripción</label><input name="descripcion" class="form-control" value="<?= e(old('descripcion', $rol['descripcion'] ?? '')) ?>"></div>
  </div>
  <?php if ($sistema): ?><div class="alert alert-info py-2">El rol Administrador siempre tiene todos los permisos.</div><?php endif; ?>
  <div class="row g-3">
    <?php foreach ($permisos as $modulo => $lista): ?>
      <div class="col-md-6 col-xl-4"><div class="border rounded p-2 h-100">
        <div class="fw-semibold mb-1"><?= e($modulo) ?></div>
        <?php foreach ($lista as $p): ?>
          <div class="form-check"><input class="form-check-input" type="checkbox" name="permisos[]" value="<?= $p['id'] ?>" id="p<?= $p['id'] ?>"
            <?= checked($sistema || in_array((int)$p['id'], $asignados, true)) ?> <?= $sistema ? 'disabled' : '' ?>>
            <label class="form-check-label small" for="p<?= $p['id'] ?>"><?= e($p['descripcion']) ?> <code class="small"><?= e($p['clave']) ?></code></label></div>
        <?php endforeach; ?>
      </div></div>
    <?php endforeach; ?>
  </div>
  <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button></div>
</form>
