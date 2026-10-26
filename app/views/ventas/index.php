<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0 me-auto">Ventas</h1>
  <?php if (can('ventas.registrar')): ?><a href="<?= url('pedidos', ['estado' => 'CONFIRMADO']) ?>" class="btn btn-success btn-sm"><i class="bi bi-cash-coin"></i> Facturar pedido</a><?php endif; ?>
</div>
<form class="card card-body mb-3 py-2" method="get">
  <input type="hidden" name="r" value="ventas">
  <div class="row g-2 align-items-end">
    <div class="col-12 col-md-3"><input name="q" class="form-control form-control-sm" placeholder="N° comprobante, cliente o documento" value="<?= e($f['q']) ?>"></div>
    <div class="col-6 col-md-2"><label class="form-label small mb-0">Desde</label><input type="date" name="desde" class="form-control form-control-sm" value="<?= e($f['desde']) ?>"></div>
    <div class="col-6 col-md-2"><label class="form-label small mb-0">Hasta</label><input type="date" name="hasta" class="form-control form-control-sm" value="<?= e($f['hasta']) ?>"></div>
    <div class="col-6 col-md-2"><select name="estado" class="form-select form-select-sm"><option value="">Todas</option>
      <option <?= selected('ACTIVA', $f['estado']) ?>>ACTIVA</option><option <?= selected('ANULADA', $f['estado']) ?>>ANULADA</option></select></div>
    <?php if (!Auth::sucursalFija()): ?>
    <div class="col-6 col-md-2"><select name="sucursal_id" class="form-select form-select-sm"><option value="">Todas las sucursales</option>
      <?php foreach ($sucursales as $s): ?><option value="<?= $s['id'] ?>" <?= selected($s['id'], $f['sucursal_id']) ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
    <div class="col-12 col-md-1"><button class="btn btn-sm btn-secondary w-100">Filtrar</button></div>
  </div>
</form>
<div class="alert alert-light border py-2 d-flex flex-wrap gap-3 small">
  <span><strong><?= num($total) ?></strong> comprobantes</span>
  <span>Total vendido (activas): <strong><?= money($monto) ?></strong></span>
</div>
<div class="card"><div class="table-responsive">
  <table class="table table-sm table-hover align-middle mb-0">
    <thead><tr><th>Comprobante</th><th>Fecha</th><th>Cliente</th><th class="d-none d-md-table-cell">Sucursal</th><th class="d-none d-lg-table-cell">Método</th><th>Estado</th><th class="text-end">Total</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $v): ?>
      <tr class="clickable <?= $v['estado'] === 'ANULADA' ? 'text-muted' : '' ?>" data-href="<?= url('ventas/ver', ['id' => $v['id']]) ?>">
        <td class="fw-semibold text-nowrap"><?= e($v['numero']) ?></td><td class="small text-nowrap"><?= fecha($v['fecha']) ?></td><td><?= e($v['cliente']) ?></td>
        <td class="d-none d-md-table-cell"><?= e($v['sucursal']) ?></td><td class="d-none d-lg-table-cell"><?= e($v['metodo_pago']) ?></td>
        <td><?= badge_estado($v['estado']) ?></td><td class="text-end text-nowrap"><?= money($v['total']) ?></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">Sin ventas en el período.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div></div>
<div class="mt-3"><?= paginacion_html($total, $lim, $page) ?></div>
