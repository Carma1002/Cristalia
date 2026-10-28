<?php
$acc = botones_exportar();
if (can('inventario.movimientos')) $acc .= '<a href="' . url('movimientos/nuevo', $producto ? ['producto_id' => $producto['id']] : []) . '" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Registrar entrada o salida</a>';
$tipos = ['ENTRADA' => 'Entradas', 'SALIDA' => 'Salidas', 'AJUSTE' => 'Ajustes', 'VENTA' => 'Ventas', 'ANULACION' => 'Anulaciones'];
?>
<?= ui_header('Entradas y salidas', 'Historial de cada cambio en las existencias (kardex). Las ventas descuentan el inventario automáticamente.', $acc) ?>

<?php if ($producto): ?>
  <div class="alert alert-info reveal"><i class="bi bi-funnel"></i><div>Mostrando sólo el historial de <b><?= e($producto['codigo'] . ' · ' . $producto['nombre']) ?></b>.</div>
    <a class="ms-auto fw-semibold" href="<?= url('movimientos') ?>">Ver todos</a></div>
<?php endif; ?>

<form method="get" class="c-card c-card__body mb-3 reveal" style="--d:1">
  <input type="hidden" name="r" value="movimientos">
  <?php if ($producto): ?><input type="hidden" name="producto_id" value="<?= $producto['id'] ?>"><?php endif; ?>
  <div class="c-filters mb-2">
    <div><label class="form-label small mb-1">Desde</label><input type="date" name="desde" class="form-control" value="<?= e($desde) ?>"></div>
    <div><label class="form-label small mb-1">Hasta</label><input type="date" name="hasta" class="form-control" value="<?= e($hasta) ?>"></div>
    <div><label class="form-label small mb-1">Tipo</label><select name="tipo" class="form-select"><option value="">Todos</option>
      <?php foreach ($tipos as $k => $t): ?><option value="<?= $k ?>" <?= selected($k, $tipo) ?>><?= $t ?></option><?php endforeach; ?></select></div>
    <?php if (!Auth::sucursalFija() && count($sucursales) > 1): ?>
    <div><label class="form-label small mb-1">Sucursal</label><select name="sucursal_id" class="form-select"><option value="">Todas</option>
      <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $suc) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
    <div class="align-self-end"><button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button></div>
  </div>
  <div class="d-flex flex-wrap gap-1">
    <?php foreach (['Hoy' => [date('Y-m-d'), date('Y-m-d')], 'Últimos 7 días' => [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')], 'Este mes' => [date('Y-m-01'), date('Y-m-d')], 'Últimos 90 días' => [date('Y-m-d', strtotime('-90 days')), date('Y-m-d')]] as $t => [$d, $h]): ?>
      <a class="btn btn-light btn-sm <?= $desde === $d && $hasta === $h ? 'active' : '' ?>" href="<?= e(url_actual(['desde' => $d, 'hasta' => $h])) ?>"><?= $t ?></a>
    <?php endforeach; ?>
  </div>
</form>

<?php if ($resumen): ?>
<div class="d-flex flex-wrap gap-2 mb-3 reveal" style="--d:2">
  <?php foreach ($resumen as $t => $c): ?><span class="c-chip"><?= badge_estado($t) ?> <b class="c-num"><?= sprintf('%+d', $c) ?></b> <small class="text-muted">unidades</small></span><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="c-card reveal" style="--d:3">
  <?php if ($rows): ?>
  <div class="c-table-wrap"><table class="c-table">
    <thead><tr><th>Fecha</th><th>Tipo</th><th>Producto</th><th class="c-hide-xs">Sucursal</th><th class="num">Cantidad</th><th class="num c-hide-xs">Antes</th><th class="num">Queda</th><th class="c-hide-xs">Motivo</th><th class="c-hide-xs">Usuario</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $m): ?>
      <tr><td class="text-nowrap small"><?= fecha($m['fecha']) ?></td><td><?= badge_estado($m['tipo']) ?></td>
        <td><span class="c-code"><?= e($m['codigo']) ?></span><div class="text-truncate" style="max-width:280px"><?= e($m['nombre']) ?></div></td>
        <td class="c-hide-xs"><?= e($m['sucursal']) ?></td>
        <td class="num strong" style="color:<?= $m['cantidad'] < 0 ? 'var(--c-bad)' : 'var(--c-ok)' ?>"><?= sprintf('%+d', $m['cantidad']) ?></td>
        <td class="num c-hide-xs text-muted"><?= (int)$m['stock_anterior'] ?></td>
        <td class="num strong"><?= (int)$m['stock_resultante'] ?></td>
        <td class="c-hide-xs small text-muted"><?= e($m['motivo']) ?></td><td class="c-hide-xs small"><?= e($m['usuario']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if (count($rows) >= 300): ?><p class="small text-muted px-3 py-2 mb-0">Se muestran los 300 más recientes. Use PDF o Excel para obtener el período completo.</p><?php endif; ?>
  <?php else: ?>
    <?= empty_state('arrow-left-right', 'No hay movimientos en este período', 'Cambie las fechas o registre una entrada de mercancía.') ?>
  <?php endif; ?>
</div>
