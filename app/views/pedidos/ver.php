<?php
$abierto = in_array($p['estado'], ['PENDIENTE', 'CONFIRMADO'], true);
$faltan = false;
foreach ($det as $d) if ($abierto && $d['disponible'] < $d['cantidad']) $faltan = true;
$acc = '';
if ($p['venta_id']) $acc .= '<a href="' . url('ventas/ver', ['id' => $p['venta_id']]) . '" class="btn btn-soft"><i class="bi bi-receipt"></i> Ver venta ' . e($p['venta_numero']) . '</a>';
if ($abierto && can('pedidos.gestionar')) $acc .= '<button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#mCancelar"><i class="bi bi-x-circle"></i> Cancelar pedido</button>';
if ($abierto && can('pedidos.gestionar') && in_array('CONFIRMADO', $transiciones, true)) {
    $acc .= '<form method="post" action="' . url('pedidos/estado') . '" class="m-0">' . csrf_field() . '<input type="hidden" name="id" value="' . $p['id'] . '"><input type="hidden" name="estado" value="CONFIRMADO"><input type="hidden" name="nota" value="Cliente confirmó el pedido"><button class="btn btn-light"><i class="bi bi-check2-circle"></i> Marcar como confirmado</button></form>';
}
if ($abierto && can('ventas.registrar')) $acc .= '<a href="' . url('ventas/registrar', ['pedido_id' => $p['id']]) . '" class="btn btn-primary"><i class="bi bi-cash-coin"></i> Registrar venta</a>';
$paso = ['PENDIENTE' => 1, 'CONFIRMADO' => 2, 'COMPLETADO' => 3, 'CANCELADO' => 0][$p['estado']];
?>
<?= ui_header('Pedido ' . $p['numero'], fecha($p['fecha']) . ' · ' . e($p['sucursal']) . ' · ' . badge_estado($p['estado']), $acc, url('pedidos')) ?>

<div class="c-card c-card__body mb-3 reveal" style="--d:1">
  <?php if ($p['estado'] === 'CANCELADO'): ?>
    <div class="c-steps"><div class="c-step is-cancel"><span class="c-step__dot"><i class="bi bi-x-lg"></i></span><span>Pedido cancelado · las unidades apartadas volvieron al inventario disponible</span></div></div>
  <?php else: ?>
  <div class="c-steps">
    <?php foreach ([1 => ['Pedido creado', 'pencil'], 2 => ['Confirmado', 'hand-thumbs-up'], 3 => ['Venta registrada', 'receipt']] as $n => [$t, $ico]):
      $cls = $n <= $paso ? 'is-done' : ($n === $paso + 1 ? 'is-current' : ''); ?>
      <div class="c-step <?= $cls ?>" style="<?= $n === 3 ? 'flex:0 0 auto' : '' ?>">
        <span class="c-step__dot"><?= $cls === 'is-done' ? '<i class="bi bi-check-lg"></i>' : '<i class="bi bi-' . $ico . '"></i>' ?></span><span class="c-hide-xs"><?= $t ?></span>
        <?php if ($n < 3): ?><span class="c-step__line"></span><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div class="row g-3 c-stagger">
  <div class="col-lg-8">
    <div class="c-card">
      <div class="c-card__head"><h2 class="c-card__title">Productos del pedido</h2><span class="c-pill c-pill--muted is-count"><?= count($det) ?></span></div>
      <div class="c-table-wrap mt-2"><table class="c-table">
        <thead><tr><th>Producto</th><th class="num">Cant.</th><th class="num c-hide-xs">Precio</th><th class="num">Subtotal</th><?php if ($abierto): ?><th class="num">Disponible</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($det as $d): $ok = !$abierto || $d['disponible'] >= $d['cantidad']; ?>
          <tr style="<?= $ok ? '' : 'background:var(--c-bad-bg)' ?>"><td><span class="c-code"><?= e($d['codigo']) ?></span><div><?= e($d['nombre']) ?></div></td>
            <td class="num strong"><?= (int)$d['cantidad'] ?></td><td class="num c-hide-xs"><?= money($d['precio_unitario']) ?></td><td class="num"><?= money($d['subtotal']) ?></td>
            <?php if ($abierto): ?><td class="num"><?= $ok ? '<span class="c-pill c-pill--ok">Sí</span>' : '<span class="c-pill c-pill--bad">Sólo ' . max(0, (int)$d['disponible']) . '</span>' ?></td><?php endif; ?></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="3" class="text-end c-hide-xs">Total del pedido</td><td class="d-sm-none" colspan="2">Total</td><td class="num c-num" style="font-size:1.15rem;color:var(--c-azul)"><?= money($p['total']) ?></td><?php if ($abierto): ?><td></td><?php endif; ?></tr></tfoot>
      </table></div>
    </div>
    <?php if ($abierto && $faltan): ?><div class="alert alert-danger mt-3"><i class="bi bi-exclamation-triangle"></i><div>Hay productos sin unidades suficientes. Registre una entrada de mercancía o cancele el pedido.</div></div><?php endif; ?>
    <?php if ($abierto && !$faltan): ?><div class="alert alert-info mt-3"><i class="bi bi-lock"></i><div>Estas unidades están <b>apartadas</b> para este cliente. Nadie más puede venderlas hasta que se registre la venta o se cancele el pedido.</div></div><?php endif; ?>
  </div>
  <div class="col-lg-4 d-flex flex-column gap-3">
    <div class="c-card c-card__body">
      <div class="c-eyebrow mb-2">Cliente</div>
      <div class="d-flex align-items-center gap-3 mb-3"><span class="c-avatar c-avatar--lg"><?= e(iniciales($p['cliente'])) ?></span>
        <div class="min-w-0"><div class="fw-semibold text-truncate"><?= e($p['cliente']) ?></div><small class="text-muted"><?= e($p['cliente_doc']) ?><?= $p['cliente_tel'] ? ' · ' . e($p['cliente_tel']) : '' ?></small></div></div>
      <dl class="c-dl"><dt>Atendido por</dt><dd><?= e($p['usuario'] ?: '—') ?></dd><dt>Sucursal</dt><dd><?= e($p['sucursal']) ?></dd></dl>
      <?php if ($p['observaciones']): ?><div class="mt-3 p-3 rounded-4 small" style="background:var(--c-bruma)"><i class="bi bi-chat-left-text me-1" style="color:var(--c-cian)"></i><?= e($p['observaciones']) ?></div><?php endif; ?>
    </div>
    <div class="c-card c-card__body">
      <div class="c-eyebrow mb-3">Historial</div>
      <ul class="c-timeline">
        <?php foreach ($hist as $h): ?>
          <li><b><?= e(ucfirst(strtolower($h['estado_nuevo']))) ?></b><small><?= fecha($h['fecha']) ?> · <?= e($h['usuario'] ?: '—') ?></small><?php if ($h['nota']): ?><small><?= e($h['nota']) ?></small><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<?php if ($abierto && can('pedidos.gestionar')): ?>
<div class="modal fade" id="mCancelar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
    <form method="post" action="<?= url('pedidos/estado') ?>" class="modal-content">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="estado" value="CANCELADO">
      <div class="modal-body pt-4">
        <div class="c-confirm-ico is-danger"><i class="bi bi-x-circle"></i></div>
        <h2 class="modal-title mb-1">¿Cancelar el pedido <?= e($p['numero']) ?>?</h2>
        <p class="text-muted">Las unidades apartadas quedarán disponibles otra vez. Esta acción no se puede deshacer.</p>
        <label class="form-label" for="nota">Motivo de la cancelación</label>
        <input name="nota" id="nota" class="form-control" required maxlength="255" placeholder="Ej.: el cliente desistió">
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Volver</button><button class="btn btn-danger">Sí, cancelar pedido</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
