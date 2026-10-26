<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0 me-auto">Sucursales</h1>
  <a href="<?= url('sucursales/crear') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nueva sucursal</a>
</div>
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
  <?php foreach ($rows as $s): ?>
  <div class="col"><div class="card h-100 <?= $s['activo'] ? '' : 'opacity-50' ?>"><div class="card-body">
    <div class="d-flex justify-content-between"><h2 class="h6 mb-1"><?= e($s['nombre']) ?></h2><span class="badge bg-<?= $s['activo'] ? 'success' : 'secondary' ?>"><?= e($s['codigo']) ?></span></div>
    <div class="small text-muted mb-2"><?= e($s['direccion'] ?: 'Sin dirección') ?><?= $s['telefono'] ? ' · ' . e($s['telefono']) : '' ?></div>
    <div class="row text-center small g-1">
      <div class="col-3"><div class="fw-bold"><?= (int)$s['usuarios'] ?></div>usuarios</div>
      <div class="col-3"><div class="fw-bold"><?= num($s['unidades']) ?></div>unidades</div>
      <div class="col-3"><div class="fw-bold"><?= (int)$s['pedidos_abiertos'] ?></div>pedidos</div>
      <div class="col-3"><div class="fw-bold"><?= num($s['ventas_mes'], 0) ?></div>ventas mes</div>
    </div>
  </div><div class="card-footer bg-transparent text-end"><a href="<?= url('sucursales/editar', ['id' => $s['id']]) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a></div></div></div>
  <?php endforeach; ?>
</div>
