<h1 class="h4 mb-3">Reportes</h1>
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
  <?php foreach ([
    ['reportes/ventas', 'graph-up', 'Ventas por período', 'Totales por día, mes, sucursal, vendedor o método de pago.'],
    ['reportes/productos', 'trophy', 'Ventas por producto', 'Unidades y montos vendidos por producto en un período.'],
    ['reportes/inventario', 'boxes', 'Inventario valorizado', 'Existencias por sucursal al costo y a precio de venta.'],
    ['reportes/rotacion', 'arrow-repeat', 'Rotación de inventario', 'Vendidas ÷ existencia promedio y días de inventario.'],
    ['reportes/movimientos', 'arrow-left-right', 'Movimientos (kardex)', 'Entradas, salidas, ajustes, ventas y anulaciones.'],
    ['stock-bajo', 'exclamation-triangle', 'Productos con poco stock', 'Productos en o por debajo del stock mínimo.'],
  ] as [$r, $i, $t, $d]): ?>
  <div class="col"><a href="<?= url($r) ?>" class="card h-100 text-decoration-none kpi"><div class="card-body">
    <i class="bi bi-<?= $i ?> fs-3 text-primary"></i><h2 class="h6 mt-2 text-body"><?= $t ?></h2><p class="small text-muted mb-0"><?= $d ?></p>
  </div></a></div>
  <?php endforeach; ?>
</div>
<p class="small text-muted mt-3">Todos los reportes se pueden exportar a PDF o a hoja de cálculo (Excel).</p>
