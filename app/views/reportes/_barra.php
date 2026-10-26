<?php
/**
 * Barra de filtros común a los reportes.
 * Variables: $ruta, $fechas (bool), $desde, $hasta, $suc, $sucursales, $extra (html opcional)
 */
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a href="<?= url('reportes') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
  <h1 class="h4 mb-0 me-auto"><?= e($title) ?></h1>
  <a href="<?= e(url_actual(['formato' => 'pdf'])) ?>" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-filetype-pdf"></i> PDF</a>
  <a href="<?= e(url_actual(['formato' => 'xlsx'])) ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
</div>
<form class="card card-body mb-3 py-2" method="get">
  <input type="hidden" name="r" value="<?= e($ruta) ?>">
  <div class="row g-2 align-items-end">
    <?php if ($fechas): ?>
      <div class="col-6 col-md-2"><label class="form-label small mb-0">Desde</label><input type="date" name="desde" class="form-control form-control-sm" value="<?= e($desde) ?>"></div>
      <div class="col-6 col-md-2"><label class="form-label small mb-0">Hasta</label><input type="date" name="hasta" class="form-control form-control-sm" value="<?= e($hasta) ?>"></div>
    <?php endif; ?>
    <?php if (!Auth::sucursalFija()): ?>
      <div class="col-6 col-md-2"><label class="form-label small mb-0">Sucursal</label><select name="sucursal_id" class="form-select form-select-sm"><option value="">Todas</option>
        <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $suc) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
    <?= $extra ?? '' ?>
    <div class="col-6 col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel"></i> Generar</button></div>
  </div>
  <?php if ($fechas): ?>
  <div class="d-flex flex-wrap gap-1 mt-2">
    <?php foreach (['Hoy' => [date('Y-m-d'), date('Y-m-d')], 'Este mes' => [date('Y-m-01'), date('Y-m-d')],
                    'Mes anterior' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
                    'Últimos 90 días' => [date('Y-m-d', strtotime('-90 days')), date('Y-m-d')], 'Este año' => [date('Y-01-01'), date('Y-m-d')]] as $t => [$d, $h]): ?>
      <a class="btn btn-sm btn-outline-secondary py-0" href="<?= e(url_actual(['desde' => $d, 'hasta' => $h])) ?>"><?= $t ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</form>
