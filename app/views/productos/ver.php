<?php
$tot = array_sum(array_column($existencias, 'cantidad'));
$res = array_sum(array_column($existencias, 'reservado'));
$min = (int)$p['stock_minimo'];
$acc = '';
if (can('pedidos.gestionar') && $p['activo'] && $tot - $res > 0) $acc .= '<a href="' . url('pedidos/crear', ['producto_id' => $p['id']]) . '" class="btn btn-light"><i class="bi bi-cart-plus"></i> Crear pedido</a>';
if (can('inventario.movimientos')) {
    $acc .= '<div class="dropdown"><button class="btn btn-primary" data-bs-toggle="dropdown"><i class="bi bi-arrow-left-right"></i> Entrada o salida <i class="bi bi-chevron-down small"></i></button>'
          . '<div class="dropdown-menu dropdown-menu-end">'
          . '<a class="dropdown-item" href="' . url('movimientos/nuevo', ['producto_id' => $p['id'], 'tipo' => 'ENTRADA']) . '"><i class="bi bi-box-arrow-in-down text-success"></i> Registrar entrada<small class="ms-auto text-muted">llegó mercancía</small></a>'
          . '<a class="dropdown-item" href="' . url('movimientos/nuevo', ['producto_id' => $p['id'], 'tipo' => 'SALIDA']) . '"><i class="bi bi-box-arrow-up text-danger"></i> Registrar salida<small class="ms-auto text-muted">daño, uso interno</small></a>'
          . '<a class="dropdown-item" href="' . url('movimientos/nuevo', ['producto_id' => $p['id'], 'tipo' => 'AJUSTE']) . '"><i class="bi bi-clipboard-check"></i> Ajustar por conteo<small class="ms-auto text-muted">corregir cantidad</small></a>'
          . '</div></div>';
}
if (can('inventario.gestionar')) {
    $acc .= '<div class="dropdown"><button class="btn btn-light" data-bs-toggle="dropdown" aria-label="Más opciones"><i class="bi bi-three-dots"></i></button><div class="dropdown-menu dropdown-menu-end">'
          . '<a class="dropdown-item" href="' . url('productos/editar', ['id' => $p['id']]) . '"><i class="bi bi-pencil"></i> Editar datos</a>'
          . '<form method="post" action="' . url('productos/estado') . '" data-confirm="' . ($p['activo'] ? '¿Desactivar este producto?' : '¿Activar este producto?') . '" data-confirm-text="' . ($p['activo'] ? 'Dejará de aparecer en el catálogo y en los pedidos. Su historial se conserva.' : 'Volverá a aparecer en el catálogo.') . '" data-confirm-ok="' . ($p['activo'] ? 'Sí, desactivar' : 'Sí, activar') . '" data-confirm-tone="' . ($p['activo'] ? 'danger' : '') . '">'
          . csrf_field() . '<input type="hidden" name="id" value="' . $p['id'] . '"><button class="dropdown-item ' . ($p['activo'] ? 'text-danger' : '') . '"><i class="bi bi-' . ($p['activo'] ? 'eye-slash' : 'eye') . '"></i> ' . ($p['activo'] ? 'Desactivar' : 'Activar') . '</button></form>'
          . '</div></div>';
}
?>
<?= ui_header($p['nombre'], '<span class="c-code">' . e($p['codigo']) . '</span> · ' . e($p['tipo_nombre']) . ($p['activo'] ? '' : ' · <span class="c-pill c-pill--muted">Inactivo</span>'), $acc, url('productos')) ?>

<div class="row g-3 c-stagger">
  <div class="col-lg-4">
    <div class="c-card overflow-hidden h-100">
      <div class="c-product__img" style="aspect-ratio:4/3">
        <?= $p['imagen'] ? '<img src="' . e($p['imagen']) . '" alt="">' : '<div class="c-ph"><img src="assets/img/cristalia-isotipo.svg" alt=""></div>' ?>
      </div>
      <div class="c-card__body">
        <div class="c-eyebrow mb-1">Precio de venta</div>
        <div class="c-display mb-3" style="font-size:2rem;color:var(--c-azul)"><?= money($p['precio']) ?></div>
        <dl class="c-dl">
          <dt>Vehículo</dt><dd><?= e(trim($p['marca_vehiculo'] . ' ' . $p['modelo_vehiculo'])) ?: '—' ?></dd>
          <dt>Años</dt><dd><?= $p['anio_desde'] ? $p['anio_desde'] . ' – ' . ($p['anio_hasta'] ?: 'actual') : '—' ?></dd>
          <?php if (can('inventario.gestionar') || can('reportes.ver')): ?><dt>Costo</dt><dd><?= money($p['costo']) ?></dd><?php endif; ?>
          <dt>Stock mínimo</dt><dd><?= $min ?> unidades</dd>
        </dl>
        <?php if ($p['descripcion']): ?><p class="small text-muted mt-3 mb-0"><?= nl2br(e($p['descripcion'])) ?></p><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-8 d-flex flex-column gap-3">
    <div class="c-card">
      <div class="c-card__head">
        <div><h2 class="c-card__title">Existencias por sucursal</h2><p class="c-card__sub">Las unidades reservadas están apartadas en pedidos que aún no se venden.</p></div>
        <?= stock_pill($tot, $min) ?>
      </div>
      <div class="c-card__body">
        <div class="row g-3">
          <?php foreach ($existencias as $e): $c = (int)$e['cantidad']; $r = (int)$e['reservado']; [$tono] = stock_estado($c, $min); $max = max($c, $min * 2, 1); ?>
            <div class="col-md-6">
              <div class="p-3 rounded-4 h-100" style="background:var(--c-bruma)">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <b class="small"><i class="bi bi-shop me-1" style="color:var(--c-cian)"></i><?= e($e['sucursal']) ?></b>
                  <span class="c-pill c-pill--<?= $tono ?>"><?= $c <= 0 ? 'Agotado' : ($min > 0 && $c <= $min ? 'Por reponer' : 'OK') ?></span>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-2"><span class="c-display" style="font-size:1.6rem"><?= num($c) ?></span><span class="small text-muted">en existencia · <?= num($c - $r) ?> libres<?= $r ? ' · ' . $r . ' reservadas' : '' ?></span></div>
                <div class="c-bar mb-1"><span class="<?= $tono === 'ok' ? '' : 'is-cian' ?>" style="width:<?= min(100, round($c / $max * 100)) ?>%;<?= $tono === 'bad' ? 'background:#E0563F' : ($tono === 'warn' ? 'background:#E8A93A' : '') ?>"></span></div>
                <div class="d-flex justify-content-between small text-muted"><span>Mínimo: <?= $min ?></span>
                  <?php if (can('inventario.movimientos') && $tono !== 'ok'): ?><a href="<?= url('movimientos/nuevo', ['producto_id' => $p['id'], 'sucursal_id' => $e['sucursal_id'], 'tipo' => 'ENTRADA']) ?>" class="fw-semibold">Reponer <i class="bi bi-arrow-right"></i></a><?php endif; ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="d-flex justify-content-between mt-3 pt-3 border-top small"><span class="text-muted">Total en todas las sucursales</span><b><?= num($tot) ?> unidades · <?= num($tot - $res) ?> libres</b></div>
      </div>
    </div>

    <?php if (can('inventario.ver')): ?>
    <div class="c-card">
      <div class="c-card__head"><div><h2 class="c-card__title">Últimas entradas y salidas</h2><p class="c-card__sub">Cada cambio de existencia queda registrado</p></div>
        <a class="btn btn-soft btn-sm" href="<?= url('movimientos', ['producto_id' => $p['id'], 'desde' => '2000-01-01']) ?>">Historial completo</a></div>
      <?php if ($movs): ?>
      <div class="c-table-wrap mt-2"><table class="c-table">
        <thead><tr><th>Fecha</th><th>Tipo</th><th class="c-hide-xs">Sucursal</th><th class="num">Cantidad</th><th class="num">Queda</th><th class="c-hide-xs">Motivo</th></tr></thead>
        <tbody>
        <?php foreach ($movs as $m): ?>
          <tr><td class="text-nowrap small"><?= fecha($m['fecha']) ?></td><td><?= badge_estado($m['tipo']) ?></td><td class="c-hide-xs"><?= e($m['sucursal']) ?></td>
            <td class="num strong" style="color:<?= $m['cantidad'] < 0 ? 'var(--c-bad)' : 'var(--c-ok)' ?>"><?= sprintf('%+d', $m['cantidad']) ?></td>
            <td class="num"><?= (int)$m['stock_resultante'] ?></td><td class="c-hide-xs small text-muted"><?= e($m['motivo']) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
      <?php else: ?><?= empty_state('arrow-left-right', 'Sin movimientos todavía') ?><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
