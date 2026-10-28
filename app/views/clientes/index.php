<?php
$acc = can('clientes.gestionar') ? '<a href="' . url('clientes/crear') . '" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nuevo cliente</a>' : '';
?>
<?= ui_header('Clientes', num($total) . ' cliente' . ($total === 1 ? '' : 's') . ($activo === '0' ? ' inactivos' : ' registrados') . ' · historial de pedidos y compras', $acc) ?>

<form method="get" class="c-filters reveal" style="--d:1">
  <input type="hidden" name="r" value="clientes">
  <div class="c-search"><i class="bi bi-search"></i><input name="q" class="form-control" placeholder="Nombre, cédula o RIF, teléfono o correo…" value="<?= e($q) ?>" aria-label="Buscar cliente"></div>
  <div class="c-seg">
    <input type="radio" name="activo" id="a1" value="1" <?= checked($activo === '1') ?> onchange="this.form.submit()"><label for="a1">Activos</label>
    <input type="radio" name="activo" id="a0" value="0" <?= checked($activo === '0') ?> onchange="this.form.submit()"><label for="a0">Inactivos</label>
    <input type="radio" name="activo" id="aa" value="" <?= checked($activo === '') ?> onchange="this.form.submit()"><label for="aa">Todos</label>
  </div>
  <button class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
</form>

<div class="c-card reveal" style="--d:2">
  <?php if ($rows): ?>
  <div class="c-table-wrap"><table class="c-table">
    <thead><tr><th>Cliente</th><th class="c-hide-xs">Teléfono</th><th class="c-hide-xs">Correo</th><th class="num">Compras</th><th class="num c-hide-xs">Total comprado</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $c): ?>
      <tr class="clickable <?= $c['activo'] ? '' : 'opacity-50' ?>" data-href="<?= url('clientes/ver', ['id' => $c['id']]) ?>">
        <td><div class="d-flex align-items-center gap-3"><span class="c-avatar" style="background:var(--c-hielo);color:var(--c-azul)"><?= e(iniciales($c['nombre'])) ?></span>
          <div class="min-w-0"><div class="strong text-truncate"><?= e($c['nombre']) ?></div><span class="c-code"><?= e(Cliente::documento($c)) ?></span></div></div></td>
        <td class="c-hide-xs"><?= e($c['telefono'] ?: '—') ?></td><td class="c-hide-xs text-muted"><?= e($c['email'] ?: '—') ?></td>
        <td class="num"><?= (int)$c['compras'] ?></td><td class="num c-hide-xs strong"><?= money($c['total_comprado']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php else: ?>
    <?= empty_state('people', $q ? 'No encontramos clientes con esa búsqueda' : 'Todavía no hay clientes', $q ? 'Revise la cédula o el nombre.' : 'Registre al primer cliente para poder crear pedidos.', can('clientes.gestionar') ? '<a href="' . url('clientes/crear') . '" class="btn btn-primary"><i class="bi bi-person-plus"></i> Registrar cliente</a>' : '') ?>
  <?php endif; ?>
</div>
<div class="mt-4 d-flex justify-content-center"><?= paginacion_html($total, $lim, $page) ?></div>
