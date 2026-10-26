<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a href="<?= url('productos') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
  <h1 class="h4 mb-0 me-auto"><?= e($p['nombre']) ?> <?= $p['activo'] ? '' : '<span class="badge bg-secondary">Inactivo</span>' ?></h1>
  <?php if (can('inventario.movimientos')): ?>
    <a href="<?= url('movimientos/nuevo', ['producto_id' => $p['id'], 'tipo' => 'ENTRADA']) ?>" class="btn btn-sm btn-success"><i class="bi bi-plus"></i> Entrada</a>
    <a href="<?= url('movimientos/nuevo', ['producto_id' => $p['id'], 'tipo' => 'SALIDA']) ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-dash"></i> Salida</a>
    <a href="<?= url('movimientos/nuevo', ['producto_id' => $p['id'], 'tipo' => 'AJUSTE']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-sliders"></i> Ajuste</a>
  <?php endif; ?>
  <?php if (can('pedidos.gestionar') && $p['activo']): ?>
    <a href="<?= url('pedidos/crear', ['producto_id' => $p['id']]) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-cart-plus"></i> Pedido</a>
  <?php endif; ?>
  <?php if (can('inventario.gestionar')): ?>
    <a href="<?= url('productos/editar', ['id' => $p['id']]) ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Editar</a>
    <form method="post" action="<?= url('productos/estado') ?>" class="d-inline" onsubmit="return confirm('¿Cambiar el estado del producto?')">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
      <button class="btn btn-sm btn-outline-<?= $p['activo'] ? 'danger' : 'success' ?>"><?= $p['activo'] ? 'Desactivar' : 'Activar' ?></button>
    </form>
  <?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card">
      <?php if ($p['imagen']): ?><img src="<?= e($p['imagen']) ?>" class="card-img-top producto-img" alt="">
      <?php else: ?><div class="producto-img placeholder-img"><i class="bi bi-image"></i></div><?php endif; ?>
      <div class="card-body">
        <dl class="row mb-0 small">
          <dt class="col-5">Código</dt><dd class="col-7"><?= e($p['codigo']) ?></dd>
          <dt class="col-5">Tipo</dt><dd class="col-7"><?= e($p['tipo_nombre']) ?></dd>
          <dt class="col-5">Vehículo</dt><dd class="col-7"><?= e(trim($p['marca_vehiculo'] . ' ' . $p['modelo_vehiculo'])) ?: '—' ?></dd>
          <dt class="col-5">Años</dt><dd class="col-7"><?= $p['anio_desde'] ? $p['anio_desde'] . ' – ' . ($p['anio_hasta'] ?: 'actual') : '—' ?></dd>
          <dt class="col-5">Precio</dt><dd class="col-7 fw-bold"><?= money($p['precio']) ?></dd>
          <?php if (can('inventario.gestionar') || can('reportes.ver')): ?><dt class="col-5">Costo</dt><dd class="col-7"><?= money($p['costo']) ?></dd><?php endif; ?>
          <dt class="col-5">Stock mínimo</dt><dd class="col-7"><?= (int)$p['stock_minimo'] ?></dd>
        </dl>
        <?php if ($p['descripcion']): ?><p class="small mt-2 mb-0"><?= nl2br(e($p['descripcion'])) ?></p><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card mb-3"><div class="card-header">Existencias por sucursal</div>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Sucursal</th><th class="text-end">Existencia</th><th class="text-end">Reservado</th><th class="text-end">Disponible</th></tr></thead>
        <tbody>
        <?php $t = [0, 0]; foreach ($existencias as $e): $t[0] += $e['cantidad']; $t[1] += $e['reservado']; $bajo = $p['stock_minimo'] > 0 && $e['cantidad'] <= $p['stock_minimo']; ?>
          <tr><td><?= e($e['sucursal']) ?> <?= $bajo ? '<span class="badge bg-danger">bajo mínimo</span>' : '' ?></td>
            <td class="text-end"><?= num($e['cantidad']) ?></td><td class="text-end text-muted"><?= num($e['reservado']) ?></td>
            <td class="text-end fw-bold"><?= num($e['cantidad'] - $e['reservado']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="fw-bold"><td>Total</td><td class="text-end"><?= num($t[0]) ?></td><td class="text-end"><?= num($t[1]) ?></td><td class="text-end"><?= num($t[0] - $t[1]) ?></td></tr></tfoot>
      </table></div>
    </div>
    <div class="card"><div class="card-header d-flex justify-content-between"><span>Últimos movimientos</span>
      <a class="small" href="<?= url('movimientos', ['producto_id' => $p['id'], 'desde' => '2000-01-01']) ?>">Ver kardex completo</a></div>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Fecha</th><th>Tipo</th><th class="d-none d-sm-table-cell">Sucursal</th><th class="text-end">Cant.</th><th class="text-end">Saldo</th><th class="d-none d-md-table-cell">Motivo</th></tr></thead>
        <tbody>
        <?php foreach ($movs as $m): ?>
          <tr><td class="text-nowrap"><?= fecha($m['fecha']) ?></td><td><?= badge_estado($m['tipo']) ?></td><td class="d-none d-sm-table-cell"><?= e($m['sucursal']) ?></td>
            <td class="text-end <?= $m['cantidad'] < 0 ? 'text-danger' : 'text-success' ?>"><?= sprintf('%+d', $m['cantidad']) ?></td>
            <td class="text-end"><?= (int)$m['stock_resultante'] ?></td><td class="d-none d-md-table-cell small"><?= e($m['motivo']) ?></td></tr>
        <?php endforeach; if (!$movs): ?><tr><td colspan="6" class="text-muted">Sin movimientos.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
  </div>
</div>
