<?php
$acciones = '';
if (can('inventario.movimientos')) $acciones .= '<a href="' . url('movimientos/nuevo') . '" class="btn btn-light"><i class="bi bi-arrow-left-right"></i> Entrada o salida</a>';
if (can('inventario.gestionar')) $acciones .= '<a href="' . url('productos/crear') . '" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo producto</a>';
$sucFija = Auth::sucursalFija();
$sucCols = !$f['sucursal_id'] && count($sucursales) <= 4 ? $sucursales : [];
$masFiltros = $f['modelo'] || $f['anio'] || $f['orden'];
$segmentos = ['' => ['Todos', $conteo['todos']], 'disponible' => ['Disponibles', $conteo['disponible']], 'bajo' => ['Por reponer', $conteo['bajo']], 'agotado' => ['Agotados', $conteo['agotado']]];
if (isset($conteo['inactivo'])) $segmentos['inactivo'] = ['Inactivos', $conteo['inactivo']];
$placeholder = '<div class="c-ph"><img src="assets/img/cristalia-isotipo.svg" alt=""></div>';
?>
<?= ui_header('Productos', num($conteo['todos']) . ' productos activos · catálogo, precios y existencias por sucursal', $acciones) ?>

<form method="get" class="c-card c-card__body mb-3 reveal" style="--d:1">
  <input type="hidden" name="r" value="productos">
  <?php if ($estado !== ''): ?><input type="hidden" name="estado" value="<?= e($estado) ?>"><?php endif; ?>
  <div class="c-filters mb-0">
    <div class="c-search"><i class="bi bi-search"></i><input name="q" class="form-control" placeholder="Código, nombre, marca o modelo del vehículo…" value="<?= e($f['q']) ?>" aria-label="Buscar"></div>
    <select name="tipo_id" class="form-select" style="width:auto" aria-label="Tipo"><option value="">Todos los tipos</option>
      <?php foreach ($tipos as $t): ?><option value="<?= $t['id'] ?>" <?= selected($t['id'], $f['tipo_id']) ?>><?= e($t['nombre']) ?></option><?php endforeach; ?></select>
    <select name="marca" class="form-select" style="width:auto" aria-label="Marca"><option value="">Todas las marcas</option>
      <?php foreach ($marcas as $m): ?><option <?= selected($m, $f['marca']) ?>><?= e($m) ?></option><?php endforeach; ?></select>
    <?php if (!$sucFija && count($sucursales) > 1): ?>
    <select name="sucursal_id" class="form-select" style="width:auto" aria-label="Sucursal"><option value="">Todas las sucursales</option>
      <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $f['sucursal_id']) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select>
    <?php endif; ?>
    <button class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
    <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#masFiltros" aria-expanded="<?= $masFiltros ? 'true' : 'false' ?>"><i class="bi bi-sliders2"></i><span class="c-hide-xs">Más filtros</span></button>
  </div>
  <div class="collapse <?= $masFiltros ? 'show' : '' ?>" id="masFiltros">
    <div class="c-filters mt-3 mb-0">
      <input name="modelo" class="form-control" style="max-width:220px" placeholder="Modelo (ej. Corolla)" value="<?= e($f['modelo']) ?>" aria-label="Modelo">
      <input name="anio" type="number" class="form-control" style="max-width:150px" placeholder="Año del vehículo" value="<?= e($f['anio']) ?>" aria-label="Año">
      <select name="orden" class="form-select" style="width:auto" aria-label="Ordenar"><option value="">Ordenar por nombre</option>
        <option value="precio_asc" <?= selected('precio_asc', $f['orden']) ?>>Precio: menor a mayor</option>
        <option value="precio_desc" <?= selected('precio_desc', $f['orden']) ?>>Precio: mayor a menor</option>
        <option value="stock" <?= selected('stock', $f['orden']) ?>>Menos existencia primero</option>
        <option value="codigo" <?= selected('codigo', $f['orden']) ?>>Código</option></select>
      <?php if ($f['q'] || $f['tipo_id'] || $f['marca'] || $masFiltros || ($f['sucursal_id'] && !$sucFija)): ?>
        <a href="<?= url('productos') ?>" class="btn btn-light btn-sm"><i class="bi bi-x-lg"></i> Limpiar filtros</a>
      <?php endif; ?>
    </div>
  </div>
</form>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 reveal" style="--d:2">
  <div class="c-seg" style="overflow-x:auto;max-width:100%">
    <?php foreach ($segmentos as $k => [$txt, $n]): ?>
      <a href="<?= e(url_actual(['estado' => $k, 'p' => 1])) ?>" class="<?= $estado === $k ? 'is-active' : '' ?>"><?= $txt ?> <span class="cnt"><?= num($n) ?></span></a>
    <?php endforeach; ?>
  </div>
  <div class="c-seg">
    <a href="<?= e(url_actual(['vista' => 'tarjetas'])) ?>" class="<?= $vista === 'tarjetas' ? 'is-active' : '' ?>" title="Vista de catálogo"><i class="bi bi-grid"></i><span class="c-hide-xs">Catálogo</span></a>
    <a href="<?= e(url_actual(['vista' => 'lista'])) ?>" class="<?= $vista === 'lista' ? 'is-active' : '' ?>" title="Vista de lista"><i class="bi bi-list-ul"></i><span class="c-hide-xs">Lista</span></a>
  </div>
</div>

<?php if (!$rows): ?>
  <div class="c-card reveal"><?= empty_state('search', 'No encontramos productos', 'Pruebe con otra palabra o quite algunos filtros.', '<a href="' . url('productos') . '" class="btn btn-soft">Ver todos los productos</a>') ?></div>

<?php elseif ($vista === 'tarjetas'): ?>
  <div class="c-products c-stagger">
    <?php foreach ($rows as $p):
      $stock = (int)$p['stock']; [$tono, $txt] = stock_estado($stock, (int)$p['stock_minimo']);
      // Sin filtro de sucursal: si alguna sucursal está en el mínimo, el producto está "por reponer"
      if ($tono === 'ok' && !$f['sucursal_id'] && $p['stock_minimo'] > 0) {
          foreach ($sucursales as $s) if ((int)($disp[$p['id']][$s['id']]['cantidad'] ?? 0) <= (int)$p['stock_minimo']) { [$tono, $txt] = ['warn', 'Por reponer']; break; }
      } ?>
      <article class="c-card c-card--link c-product">
        <a href="<?= url('productos/ver', ['id' => $p['id']]) ?>" class="c-product__img" aria-label="<?= e($p['nombre']) ?>">
          <?= $p['imagen'] ? '<img src="' . e($p['imagen']) . '" alt="" loading="lazy">' : $placeholder ?>
          <span class="c-pill c-pill--<?= $p['activo'] ? $tono : 'muted' ?>"><?= $p['activo'] ? $txt : 'Inactivo' ?></span>
        </a>
        <div class="c-product__body">
          <div class="d-flex justify-content-between gap-2"><span class="c-code"><?= e($p['codigo']) ?></span><span class="c-code text-truncate"><?= e($p['tipo_nombre']) ?></span></div>
          <a href="<?= url('productos/ver', ['id' => $p['id']]) ?>" class="c-product__name"><?= e($p['nombre']) ?></a>
          <?php if ($p['marca_vehiculo']): ?><div class="c-product__meta"><i class="bi bi-car-front"></i> <?= e(trim($p['marca_vehiculo'] . ' ' . $p['modelo_vehiculo'])) ?><?= $p['anio_desde'] ? ' · ' . $p['anio_desde'] . '–' . ($p['anio_hasta'] ?: 'act.') : '' ?></div><?php endif; ?>
          <div class="d-flex justify-content-between align-items-end mt-1">
            <span class="c-product__price"><?= money($p['precio']) ?></span>
            <span class="small text-muted"><?= num($stock) ?> en stock</span>
          </div>
          <?php if (!$f['sucursal_id'] && !empty($disp[$p['id']]) && count($sucursales) > 1): ?>
            <div class="c-product__stock"><?php foreach ($disp[$p['id']] as $d): ?><span><?= e($d['nombre']) ?> <b><?= (int)$d['cantidad'] ?></b></span><?php endforeach; ?></div>
          <?php endif; ?>
          <div class="c-product__foot">
            <a href="<?= url('productos/ver', ['id' => $p['id']]) ?>" class="btn btn-soft btn-sm">Ver detalle</a>
            <?php if (can('pedidos.gestionar') && $stock > 0 && $p['activo']): ?><a href="<?= url('pedidos/crear', ['producto_id' => $p['id']]) ?>" class="btn btn-primary btn-sm"><i class="bi bi-cart-plus"></i> Pedir</a><?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

<?php else: ?>
  <div class="c-card reveal" style="--d:3"><div class="c-table-wrap">
    <table class="c-table">
      <thead><tr>
        <th>Producto</th><th class="c-hide-xs">Tipo</th>
        <?php if ($sucCols): foreach ($sucCols as $s): ?><th class="num c-hide-xs"><?= e($s['nombre']) ?></th><?php endforeach; endif; ?>
        <th class="num"><?= $sucCols ? 'Total' : 'Existencia' ?></th><th class="num c-hide-xs">Mínimo</th><th class="num">Precio</th><th>Estado</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $p): $stock = (int)$p['stock']; ?>
        <tr class="clickable" data-href="<?= url('productos/ver', ['id' => $p['id']]) ?>">
          <td><span class="c-code"><?= e($p['codigo']) ?></span><div class="strong"><?= e($p['nombre']) ?></div>
            <?php if ($p['marca_vehiculo']): ?><small class="text-muted"><?= e(trim($p['marca_vehiculo'] . ' ' . $p['modelo_vehiculo'])) ?></small><?php endif; ?></td>
          <td class="c-hide-xs text-muted"><?= e($p['tipo_nombre']) ?></td>
          <?php if ($sucCols): foreach ($sucCols as $s): $c = (int)($disp[$p['id']][$s['id']]['cantidad'] ?? 0); $bajo = $p['stock_minimo'] > 0 && $c <= $p['stock_minimo']; ?>
            <td class="num c-hide-xs <?= $bajo ? ($c === 0 ? 'text-danger fw-semibold' : 'fw-semibold') : '' ?>" style="<?= $bajo && $c > 0 ? 'color:var(--c-warn)' : '' ?>"><?= num($c) ?></td>
          <?php endforeach; endif; ?>
          <td class="num strong"><?= num($stock) ?></td>
          <td class="num c-hide-xs text-muted"><?= (int)$p['stock_minimo'] ?></td>
          <td class="num"><?= money($p['precio']) ?></td>
          <td><?= $p['activo'] ? stock_pill($stock, (int)$p['stock_minimo'], false) : '<span class="c-pill c-pill--muted">Inactivo</span>' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
  <?php if ($sucCols): ?><p class="small text-muted mt-2 mb-0"><span style="color:var(--c-warn)">■</span> en el mínimo · <span class="text-danger">■</span> agotado en esa sucursal</p><?php endif; ?>
<?php endif; ?>

<div class="mt-4 d-flex justify-content-center"><?= paginacion_html($total, $lim, $page) ?></div>
