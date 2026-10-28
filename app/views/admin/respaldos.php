<?= ui_header('Respaldos', 'Copias de seguridad de toda la información. Descárguelas y guárdelas fuera del servidor.',
    '<form method="post" action="' . url('respaldos/generar') . '" class="m-0">' . csrf_field() . '<button class="btn btn-primary"><i class="bi bi-cloud-plus"></i> Crear respaldo ahora</button></form>') ?>

<div class="row g-3 c-stagger">
  <div class="col-lg-7">
    <div class="c-card h-100">
      <div class="c-card__head"><div><h2 class="c-card__title">Respaldos guardados</h2><p class="c-card__sub">Consejo: cree y descargue uno al cierre de cada día.</p></div><span class="c-pill c-pill--muted is-count"><?= count($rows) ?></span></div>
      <?php if ($rows): ?>
      <ul class="c-list mt-2">
        <?php foreach ($rows as $r): ?>
          <li><div>
            <span class="c-list__ico"><i class="bi bi-database"></i></span>
            <span class="c-list__main"><span class="c-list__title d-block"><?= fecha($r['fecha']) ?></span><span class="c-list__sub d-block"><?= e($r['archivo']) ?> · <?= num($r['tamano'] / 1024, 1) ?> KB</span></span>
            <a class="btn btn-soft btn-sm" href="<?= url('respaldos/descargar', ['archivo' => $r['archivo']]) ?>"><i class="bi bi-download"></i><span class="c-hide-xs">Descargar</span></a>
            <form method="post" action="<?= url('respaldos/eliminar') ?>" class="m-0" data-confirm="¿Eliminar este respaldo del servidor?" data-confirm-text="Si no lo descargó antes, se perderá." data-confirm-ok="Sí, eliminar" data-confirm-tone="danger"><?= csrf_field() ?>
              <input type="hidden" name="archivo" value="<?= e($r['archivo']) ?>"><button class="btn btn-light btn-sm" title="Eliminar"><i class="bi bi-trash3"></i></button></form>
          </div></li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><?= empty_state('cloud', 'Aún no hay respaldos', 'Pulse “Crear respaldo ahora” para guardar una copia de toda la información.') ?><?php endif; ?>
    </div>
  </div>
  <div class="col-lg-5">
    <form method="post" action="<?= url('respaldos/restaurar') ?>" enctype="multipart/form-data" class="c-card c-card__body h-100" style="border-color:#F0C9C4"
          data-confirm="¿Restaurar la base de datos?" data-confirm-text="Se reemplazará TODA la información actual por la del respaldo. Antes se guardará una copia automática." data-confirm-ok="Sí, restaurar" data-confirm-tone="danger">
      <?= csrf_field() ?>
      <h2 class="c-section-title" style="color:var(--c-bad)"><i class="bi bi-arrow-counterclockwise"></i> Recuperar información</h2>
      <p class="small text-muted">Use esta opción sólo si necesita volver a un punto anterior (por ejemplo, después de un error grave).</p>
      <label class="form-label">Desde un respaldo guardado</label>
      <select name="archivo" class="form-select mb-3"><option value="">— o suba un archivo abajo —</option>
        <?php foreach ($rows as $r): ?><option value="<?= e($r['archivo']) ?>"><?= fecha($r['fecha']) ?> · <?= e($r['archivo']) ?></option><?php endforeach; ?></select>
      <label class="form-label">O desde un archivo .sql descargado</label>
      <input type="file" name="respaldo" accept=".sql" class="form-control mb-3">
      <label class="form-label">Escriba <code>RESTAURAR</code> para confirmar</label>
      <input name="confirmar" class="form-control mb-3" required autocomplete="off" placeholder="RESTAURAR">
      <button class="btn btn-danger w-100"><i class="bi bi-arrow-counterclockwise"></i> Restaurar</button>
    </form>
  </div>
</div>
