<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0 me-auto">Catálogo digital <span class="text-muted fs-6">(<?= num($total) ?> productos)</span></h1>
</div>
<form class="card card-body mb-3 py-2" method="get">
  <input type="hidden" name="r" value="catalogo">
  <div class="row g-2">
    <div class="col-12 col-md-3"><input name="q" class="form-control form-control-sm" placeholder="Código o nombre" value="<?= e($f['q']) ?>"></div>
    <div class="col-6 col-md-2"><select name="tipo_id" class="form-select form-select-sm"><option value="">Tipo</option>
      <?php foreach ($tipos as $t): ?><option value="<?= $t['id'] ?>" <?= selected($t['id'], $f['tipo_id']) ?>><?= e($t['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-2"><select name="marca" class="form-select form-select-sm"><option value="">Marca</option>
      <?php foreach ($marcas as $m): ?><option <?= selected($m, $f['marca']) ?>><?= e($m) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-2"><input name="modelo" class="form-control form-control-sm" placeholder="Modelo" value="<?= e($f['modelo']) ?>"></div>
    <div class="col-6 col-md-1"><input name="anio" type="number" class="form-control form-control-sm" placeholder="Año" value="<?= e($f['anio']) ?>"></div>
    <div class="col-6 col-md-2"><select name="sucursal_id" class="form-select form-select-sm"><option value="">Todas las sucursales</option>
      <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $f['sucursal_id']) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-2"><select name="orden" class="form-select form-select-sm">
      <option value="">Orden: nombre</option><option value="precio_asc" <?= selected('precio_asc', $f['orden']) ?>>Precio ↑</option>
      <option value="precio_desc" <?= selected('precio_desc', $f['orden']) ?>>Precio ↓</option><option value="codigo" <?= selected('codigo', $f['orden']) ?>>Código</option></select></div>
    <div class="col-6 col-md-2 d-flex align-items-center"><div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="disp" value="1" id="disp" <?= checked((bool)$f['solo_disponibles']) ?>>
      <label class="form-check-label small" for="disp">Solo disponibles</label></div></div>
    <div class="col-12 col-md-2 ms-md-auto"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Buscar</button></div>
  </div>
</form>

<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-4 g-3">
  <?php foreach ($rows as $p): $stock = (int)$p['stock']; ?>
  <div class="col">
    <div class="card h-100 producto-card">
      <?php if ($p['imagen']): ?><img src="<?= e($p['imagen']) ?>" class="card-img-top producto-img" alt="<?= e($p['nombre']) ?>" loading="lazy">
      <?php else: ?><div class="producto-img placeholder-img"><i class="bi bi-car-front"></i></div><?php endif; ?>
      <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between small text-muted mb-1"><span><?= e($p['codigo']) ?></span><span><?= e($p['tipo_nombre']) ?></span></div>
        <h2 class="h6 mb-1"><?= e($p['nombre']) ?></h2>
        <div class="small text-muted mb-2"><?= e(trim($p['marca_vehiculo'] . ' ' . $p['modelo_vehiculo'])) ?>
          <?= $p['anio_desde'] ? '· ' . $p['anio_desde'] . '–' . ($p['anio_hasta'] ?: 'actual') : '' ?></div>
        <?php if ($p['descripcion']): ?><p class="small mb-2 text-truncate-2"><?= e($p['descripcion']) ?></p><?php endif; ?>
        <div class="mt-auto">
          <div class="d-flex justify-content-between align-items-center">
            <span class="fs-5 fw-bold"><?= money($p['precio']) ?></span>
            <?php if ($stock <= 0): ?><span class="badge bg-danger">Agotado</span>
            <?php elseif ($p['stock_minimo'] > 0 && $stock <= $p['stock_minimo']): ?><span class="badge bg-warning text-dark">Pocas unidades (<?= $stock ?>)</span>
            <?php else: ?><span class="badge bg-success">Disponible (<?= $stock ?>)</span><?php endif; ?>
          </div>
          <?php if (!empty($disp[$p['id']]) && !$f['sucursal_id']): ?>
            <div class="small text-muted mt-1"><?= implode(' · ', array_map(fn($d) => e($d['nombre']) . ': ' . (int)$d['cantidad'], $disp[$p['id']])) ?></div>
          <?php endif; ?>
          <div class="d-flex gap-2 mt-2">
            <?php if (can('inventario.ver')): ?><a href="<?= url('productos/ver', ['id' => $p['id']]) ?>" class="btn btn-sm btn-outline-secondary flex-fill">Detalle</a><?php endif; ?>
            <?php if (can('pedidos.gestionar') && $stock > 0): ?><a href="<?= url('pedidos/crear', ['producto_id' => $p['id']]) ?>" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-cart-plus"></i> Pedido</a><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php if (!$rows): ?><div class="text-center text-muted py-5">No se encontraron productos con esos criterios.</div><?php endif; ?>
<div class="mt-3"><?= paginacion_html($total, $lim, $page) ?></div>
