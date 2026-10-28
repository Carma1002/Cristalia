<?php
$extra = '<div><label class="form-label small mb-1">Tipo</label><select name="tipo_id" class="form-select"><option value="">Todos</option>';
foreach ($tipos as $t) $extra .= '<option value="' . $t['id'] . '" ' . selected($t['id'], $tipo) . '>' . e($t['nombre']) . '</option>';
$extra .= '</select></div><div><label class="form-label small mb-1">Ordenar</label><select name="orden" class="form-select">'
        . '<option value="">Por nombre</option><option value="rotacion" ' . selected('rotacion', $orden) . '>Mayor rotación</option>'
        . '<option value="valor" ' . selected('valor', $orden) . '>Mayor valor</option><option value="existencia" ' . selected('existencia', $orden) . '>Menos existencia</option></select></div>'
        . '<div class="align-self-end pb-2"><div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="reponer" value="1" id="rep" ' . checked($reponer) . '><label class="form-check-label small" for="rep">Sólo por reponer</label></div></div>';
$mon = ajuste('moneda', '$') . ' ';
$tonos = ['Disponible' => 'ok', 'Por reponer' => 'warn', 'Agotado' => 'bad'];
?>
<?= ui_header('Inventario', 'Existencias, valor, estado de stock y rotación de cada producto.', botones_exportar()) ?>
<?= View::partial('reportes/_filtros', compact('desde', 'hasta', 'suc', 'sucursales', 'extra') + ['ruta' => 'reportes/inventario', 'etiquetaPeriodo' => 'Rotación desde']) ?>

<div class="row g-3 mb-3 c-stagger">
  <div class="col-6 col-xl-3"><div class="c-card c-kpi"><span class="c-kpi__label">Unidades en existencia</span><div class="c-kpi__value" data-count="<?= $tot['unidades'] ?>"><?= num($tot['unidades']) ?></div></div></div>
  <div class="col-6 col-xl-3"><div class="c-card c-kpi"><span class="c-kpi__label">Valor al costo</span><div class="c-kpi__value" style="color:var(--c-azul)" data-count="<?= $tot['costo'] ?>" data-decimals="2" data-prefix="<?= e($mon) ?>"><?= money($tot['costo']) ?></div></div></div>
  <div class="col-6 col-xl-3"><div class="c-card c-kpi"><span class="c-kpi__label">Valor a precio de venta</span><div class="c-kpi__value" data-count="<?= $tot['venta'] ?>" data-decimals="2" data-prefix="<?= e($mon) ?>"><?= money($tot['venta']) ?></div></div></div>
  <div class="col-6 col-xl-3"><a class="c-card c-card--link c-kpi" href="<?= e(url_actual(['reponer' => $reponer ? '' : 1])) ?>"><span class="c-kpi__label">Productos por reponer</span><div class="c-kpi__value" style="color:var(--c-warn)" data-count="<?= $tot['reponer'] ?>"><?= num($tot['reponer']) ?></div></a></div>
</div>

<div class="c-card reveal" style="--d:3">
  <div class="c-card__head"><div><h2 class="c-card__title">Detalle por producto</h2>
    <p class="c-card__sub"><b>Rotación</b> = unidades vendidas ÷ existencia promedio del período. <b>Días de inventario</b> = cuántos días dura el stock al ritmo de venta actual.</p></div></div>
  <?php if ($rows): ?>
  <div class="c-table-wrap mt-2"><table class="c-table">
    <thead><tr><th>Producto</th><th class="c-hide-xs">Tipo</th><th class="num">Exist.</th><th class="num c-hide-xs">Mín.</th><th>Estado</th><th class="num c-hide-xs">Valor costo</th><th class="num">Vendidas</th><th class="num">Rotación</th><th class="num c-hide-xs">Días inv.</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
      <tr class="clickable" data-href="<?= url('productos/ver', ['id' => $r['id']]) ?>">
        <td><span class="c-code"><?= e($r['codigo']) ?></span><div class="text-truncate" style="max-width:300px"><?= e($r['nombre']) ?></div></td>
        <td class="c-hide-xs text-muted"><?= e($r['tipo']) ?></td><td class="num strong"><?= num($r['existencia']) ?></td><td class="num c-hide-xs text-muted"><?= (int)$r['stock_minimo'] ?></td>
        <td><span class="c-pill c-pill--<?= $tonos[$r['estado']] ?>"><?= e($r['estado']) ?></span></td>
        <td class="num c-hide-xs"><?= money($r['valor_costo']) ?></td><td class="num"><?= num($r['vendidas']) ?></td>
        <td class="num strong"><?= e($r['rot_txt']) ?></td><td class="num c-hide-xs"><?= e($r['dias_txt']) ?></td></tr>
    <?php endforeach; ?></tbody>
    <tfoot><tr><td>Total · <?= count($rows) ?> productos</td><td class="c-hide-xs"></td><td class="num"><?= num($tot['unidades']) ?></td><td class="c-hide-xs"></td><td></td><td class="num c-hide-xs"><?= money($tot['costo']) ?></td><td class="num"><?= num($tot['vendidas']) ?></td><td></td><td class="c-hide-xs"></td></tr></tfoot>
  </table></div>
  <?php else: ?><?= empty_state('boxes', 'No hay productos con estos filtros') ?><?php endif; ?>
</div>
