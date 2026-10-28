<?php
/** Filtros comunes de los reportes. Variables: $ruta, $desde, $hasta, $suc, $sucursales, $extra (html), $etiquetaPeriodo */
$rangos = ['Este mes' => [date('Y-m-01'), date('Y-m-d')], 'Mes pasado' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
           'Últimos 90 días' => [date('Y-m-d', strtotime('-90 days')), date('Y-m-d')], 'Este año' => [date('Y-01-01'), date('Y-m-d')],
           'Últimos 12 meses' => [date('Y-m-01', strtotime('-11 months')), date('Y-m-d')]];
?>
<form method="get" class="c-card c-card__body mb-3 reveal" style="--d:1">
  <input type="hidden" name="r" value="<?= e($ruta) ?>">
  <div class="c-filters mb-2">
    <div><label class="form-label small mb-1"><?= e($etiquetaPeriodo ?? 'Desde') ?></label><input type="date" name="desde" class="form-control" value="<?= e($desde) ?>"></div>
    <div><label class="form-label small mb-1">Hasta</label><input type="date" name="hasta" class="form-control" value="<?= e($hasta) ?>"></div>
    <?php if (!Auth::sucursalFija() && count($sucursales) > 1): ?>
    <div><label class="form-label small mb-1">Sucursal</label><select name="sucursal_id" class="form-select"><option value="">Todas</option>
      <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $suc) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
    <?= $extra ?? '' ?>
    <div class="align-self-end"><button class="btn btn-primary"><i class="bi bi-funnel"></i> Generar</button></div>
  </div>
  <div class="d-flex flex-wrap gap-1">
    <?php foreach ($rangos as $t => [$d, $h]): ?><a class="btn btn-light btn-sm <?= $desde === $d && $hasta === $h ? 'active' : '' ?>" href="<?= e(url_actual(['desde' => $d, 'hasta' => $h])) ?>"><?= $t ?></a><?php endforeach; ?>
  </div>
</form>
