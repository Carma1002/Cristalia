<?php
$acc = can('pedidos.gestionar') ? '<a href="' . url('pedidos/crear') . '" class="btn btn-primary"><i class="bi bi-cart-plus"></i> Nuevo pedido</a>' : '';
$abiertos = $conteo['PENDIENTE'] + $conteo['CONFIRMADO'];
$seg = ['' => ['Todos', array_sum($conteo)], 'abiertos' => ['Por completar', $abiertos], 'COMPLETADO' => ['Completados', $conteo['COMPLETADO']], 'CANCELADO' => ['Cancelados', $conteo['CANCELADO']]];
$hayFiltros = $f['q'] || $f['desde'] || $f['hasta'] || ($f['sucursal_id'] && !Auth::sucursalFija());
?>
<?= ui_header('Pedidos', 'Un pedido aparta los productos; al registrar la venta se descuentan del inventario.', $acc) ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 reveal" style="--d:1">
  <div class="c-seg" style="overflow-x:auto;max-width:100%">
    <?php foreach ($seg as $k => [$t, $n]): ?>
      <a href="<?= e(url_actual(['estado' => $k, 'p' => 1])) ?>" class="<?= $f['estado'] === $k ? 'is-active' : '' ?>"><?= $t ?> <span class="cnt"><?= num($n) ?></span></a>
    <?php endforeach; ?>
  </div>
  <button class="btn btn-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filtros"><i class="bi bi-sliders2"></i> Filtrar</button>
</div>

<form method="get" class="c-card c-card__body mb-3 collapse <?= $hayFiltros ? 'show' : '' ?>" id="filtros">
  <input type="hidden" name="r" value="pedidos"><input type="hidden" name="estado" value="<?= e($f['estado']) ?>">
  <div class="c-filters mb-0">
    <div class="c-search"><i class="bi bi-search"></i><input name="q" class="form-control" placeholder="N° de pedido, cliente o cédula…" value="<?= e($f['q']) ?>"></div>
    <input type="date" name="desde" class="form-control" style="max-width:170px" value="<?= e($f['desde']) ?>" aria-label="Desde">
    <input type="date" name="hasta" class="form-control" style="max-width:170px" value="<?= e($f['hasta']) ?>" aria-label="Hasta">
    <?php if (!Auth::sucursalFija() && count($sucursales) > 1): ?>
    <select name="sucursal_id" class="form-select" style="width:auto"><option value="">Todas las sucursales</option>
      <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $f['sucursal_id']) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select>
    <?php endif; ?>
    <button class="btn btn-primary">Aplicar</button>
    <?php if ($hayFiltros): ?><a class="btn btn-light" href="<?= url('pedidos') ?>">Limpiar</a><?php endif; ?>
  </div>
</form>

<div class="c-card reveal" style="--d:2">
  <?php if ($rows): ?>
  <div class="c-table-wrap"><table class="c-table">
    <thead><tr><th>Pedido</th><th>Cliente</th><th class="c-hide-xs">Sucursal</th><th class="c-hide-xs">Productos</th><th>Estado</th><th class="num">Total</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $p): ?>
      <tr class="clickable" data-href="<?= url('pedidos/ver', ['id' => $p['id']]) ?>">
        <td><div class="strong"><?= e($p['numero']) ?></div><small class="text-muted"><?= fecha($p['fecha']) ?></small></td>
        <td><div class="text-truncate" style="max-width:260px"><?= e($p['cliente']) ?></div></td>
        <td class="c-hide-xs"><?= e($p['sucursal']) ?></td><td class="c-hide-xs"><?= (int)$p['items'] ?> producto<?= (int)$p['items'] === 1 ? '' : 's' ?></td>
        <td><?= badge_estado($p['estado']) ?></td><td class="num strong"><?= money($p['total']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php else: ?>
    <?= empty_state('clipboard-check', 'No hay pedidos aquí', 'Cambie el filtro o cree un pedido nuevo.', can('pedidos.gestionar') ? '<a href="' . url('pedidos/crear') . '" class="btn btn-primary"><i class="bi bi-cart-plus"></i> Nuevo pedido</a>' : '') ?>
  <?php endif; ?>
</div>
<div class="mt-4 d-flex justify-content-center"><?= paginacion_html($total, $lim, $page) ?></div>
