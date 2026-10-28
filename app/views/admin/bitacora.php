<?php
$ico = ['seguridad' => 'shield-lock', 'inventario' => 'box-seam', 'pedidos' => 'clipboard-check', 'ventas' => 'receipt', 'clientes' => 'people', 'reportes' => 'bar-chart', 'administracion' => 'sliders'];
$hay = input('modulo') || input('usuario_id') || input('desde') || input('hasta') || input('q');
?>
<?= ui_header('Bitácora', num($total) . ' operaciones registradas · quién hizo qué y cuándo.') ?>

<form method="get" class="c-card c-card__body mb-3 reveal" style="--d:1">
  <input type="hidden" name="r" value="bitacora">
  <div class="c-filters mb-0">
    <div class="c-search"><i class="bi bi-search"></i><input name="q" class="form-control" placeholder="Buscar en el detalle…" value="<?= e(input('q')) ?>"></div>
    <select name="modulo" class="form-select" style="width:auto"><option value="">Todos los módulos</option>
      <?php foreach ($modulos as $m): ?><option value="<?= e($m) ?>" <?= selected($m, input('modulo')) ?>><?= e(ucfirst($m)) ?></option><?php endforeach; ?></select>
    <select name="usuario_id" class="form-select" style="width:auto"><option value="">Todos los usuarios</option>
      <?php foreach ($usuarios as $u): ?><option value="<?= $u['id'] ?>" <?= selected($u['id'], input('usuario_id')) ?>><?= e($u['nombre']) ?></option><?php endforeach; ?></select>
    <input type="date" name="desde" class="form-control" style="max-width:165px" value="<?= e(input('desde')) ?>" aria-label="Desde">
    <input type="date" name="hasta" class="form-control" style="max-width:165px" value="<?= e(input('hasta')) ?>" aria-label="Hasta">
    <button class="btn btn-primary">Filtrar</button>
    <?php if ($hay): ?><a class="btn btn-light" href="<?= url('bitacora') ?>">Limpiar</a><?php endif; ?>
  </div>
</form>

<div class="c-card reveal" style="--d:2">
  <?php if ($rows): ?>
  <div class="c-table-wrap"><table class="c-table">
    <thead><tr><th>Fecha</th><th>Usuario</th><th>Operación</th><th>Detalle</th><th class="c-hide-xs">IP</th></tr></thead>
    <tbody><?php foreach ($rows as $b): $alerta = str_contains($b['accion'], 'fallido') || str_contains($b['accion'], 'anular') || str_contains($b['accion'], 'restaurar'); ?>
      <tr style="<?= $alerta ? 'background:var(--c-warn-bg)' : '' ?>">
        <td class="text-nowrap small"><?= fecha($b['fecha']) ?></td><td class="small"><?= e($b['usuario'] ?? '—') ?></td>
        <td class="text-nowrap"><i class="bi bi-<?= $ico[$b['modulo']] ?? 'dot' ?> me-1" style="color:var(--c-cian)"></i><span class="small fw-semibold"><?= e(str_replace('_', ' ', $b['accion'])) ?></span></td>
        <td class="small"><?= e($b['detalle']) ?></td><td class="c-hide-xs small text-muted"><?= e($b['ip']) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php else: ?><?= empty_state('journal-text', 'No hay operaciones con estos filtros') ?><?php endif; ?>
</div>
<div class="mt-4 d-flex justify-content-center"><?= paginacion_html($total, $lim, $page) ?></div>
