<?php
$acc = can('ventas.registrar') ? '<a href="' . url('pedidos', ['estado' => 'abiertos']) . '" class="btn btn-primary"><i class="bi bi-cash-coin"></i> Cobrar un pedido</a>' : '';
$rangos = ['Hoy' => [date('Y-m-d'), date('Y-m-d')], 'Esta semana' => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d')], 'Este mes' => [date('Y-m-01'), date('Y-m-d')],
           'Mes pasado' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))]];
?>
<?= ui_header('Ventas', 'Cada venta nace de un pedido y descuenta el inventario automáticamente.', $acc) ?>

<form method="get" class="c-card c-card__body mb-3 reveal" style="--d:1">
  <input type="hidden" name="r" value="ventas">
  <div class="c-filters mb-2">
    <div class="c-search"><i class="bi bi-search"></i><input name="q" class="form-control" placeholder="N° de comprobante, cliente o cédula…" value="<?= e($f['q']) ?>"></div>
    <input type="date" name="desde" class="form-control" style="max-width:170px" value="<?= e($f['desde']) ?>" aria-label="Desde">
    <input type="date" name="hasta" class="form-control" style="max-width:170px" value="<?= e($f['hasta']) ?>" aria-label="Hasta">
    <select name="estado" class="form-select" style="width:auto"><option value="">Activas y anuladas</option><option value="ACTIVA" <?= selected('ACTIVA', $f['estado']) ?>>Sólo activas</option><option value="ANULADA" <?= selected('ANULADA', $f['estado']) ?>>Sólo anuladas</option></select>
    <?php if (!Auth::sucursalFija() && count($sucursales) > 1): ?>
    <select name="sucursal_id" class="form-select" style="width:auto"><option value="">Todas las sucursales</option>
      <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $f['sucursal_id']) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select>
    <?php endif; ?>
    <button class="btn btn-primary">Buscar</button>
  </div>
  <div class="d-flex flex-wrap gap-1">
    <?php foreach ($rangos as $t => [$d, $h]): ?><a class="btn btn-light btn-sm <?= $f['desde'] === $d && $f['hasta'] === $h ? 'active' : '' ?>" href="<?= e(url_actual(['desde' => $d, 'hasta' => $h, 'p' => 1])) ?>"><?= $t ?></a><?php endforeach; ?>
  </div>
</form>

<div class="row g-3 mb-3 c-stagger">
  <div class="col-6 col-md-4"><div class="c-card c-kpi"><span class="c-kpi__label">Comprobantes</span><div class="c-kpi__value" data-count="<?= $total ?>"><?= num($total) ?></div></div></div>
  <div class="col-6 col-md-4"><div class="c-card c-kpi"><span class="c-kpi__label">Total vendido (activas)</span><div class="c-kpi__value" data-count="<?= $monto ?>" data-decimals="2" data-prefix="<?= e(ajuste('moneda', '$') . ' ') ?>"><?= money($monto) ?></div></div></div>
  <div class="col-md-4 c-hide-xs"><div class="c-card c-kpi"><span class="c-kpi__label">Período</span><div class="c-kpi__value" style="font-size:1.1rem"><?= fecha($f['desde'], false) ?> – <?= fecha($f['hasta'], false) ?></div></div></div>
</div>

<div class="c-card reveal" style="--d:3">
  <?php if ($rows): ?>
  <div class="c-table-wrap"><table class="c-table">
    <thead><tr><th>Comprobante</th><th>Cliente</th><th class="c-hide-xs">Sucursal</th><th class="c-hide-xs">Pago</th><th>Estado</th><th class="num">Total</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $v): ?>
      <tr class="clickable <?= $v['estado'] === 'ANULADA' ? 'opacity-50' : '' ?>" data-href="<?= url('ventas/ver', ['id' => $v['id']]) ?>">
        <td><div class="strong"><?= e($v['numero']) ?></div><small class="text-muted"><?= fecha($v['fecha']) ?></small></td>
        <td><div class="text-truncate" style="max-width:260px"><?= e($v['cliente']) ?></div></td>
        <td class="c-hide-xs"><?= e($v['sucursal']) ?></td><td class="c-hide-xs"><?= e($v['metodo_pago']) ?></td>
        <td><?= badge_estado($v['estado']) ?></td><td class="num strong"><?= money($v['total']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php else: ?>
    <?= empty_state('receipt', 'No hay ventas en este período', 'Cambie las fechas o cobre un pedido pendiente.') ?>
  <?php endif; ?>
</div>
<div class="mt-4 d-flex justify-content-center"><?= paginacion_html($total, $lim, $page) ?></div>
