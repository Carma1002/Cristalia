<h1 class="h4 mb-3">Copias de seguridad</h1>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card"><div class="card-header d-flex justify-content-between align-items-center">
      <span>Respaldos en el servidor</span>
      <form method="post" action="<?= url('respaldos/generar') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-primary"><i class="bi bi-database-add"></i> Generar respaldo ahora</button></form>
    </div>
      <div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead><tr><th>Archivo</th><th class="d-none d-sm-table-cell">Fecha</th><th class="text-end">Tamaño</th><th></th></tr></thead>
        <tbody><?php foreach ($rows as $r): ?>
          <tr><td class="small text-break"><?= e($r['archivo']) ?></td><td class="d-none d-sm-table-cell small"><?= fecha($r['fecha']) ?></td>
            <td class="text-end small"><?= num($r['tamano'] / 1024, 1) ?> KB</td>
            <td class="text-end text-nowrap">
              <a class="btn btn-sm btn-outline-success" href="<?= url('respaldos/descargar', ['archivo' => $r['archivo']]) ?>" title="Descargar"><i class="bi bi-download"></i></a>
              <form method="post" action="<?= url('respaldos/eliminar') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este respaldo del servidor?')"><?= csrf_field() ?>
                <input type="hidden" name="archivo" value="<?= e($r['archivo']) ?>"><button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button></form>
            </td></tr>
        <?php endforeach; if (!$rows): ?><tr><td colspan="4" class="text-muted text-center py-3">Aún no hay respaldos.</td></tr><?php endif; ?></tbody>
      </table></div>
    </div>
    <p class="small text-muted mt-2">Recomendación: genere un respaldo al cierre de cada día y descárguelo a otro equipo o a la nube. El hosting gratuito no garantiza la conservación de archivos.</p>
  </div>
  <div class="col-lg-5">
    <div class="card border-danger"><div class="card-header text-danger"><i class="bi bi-exclamation-octagon"></i> Restaurar (recuperación de datos)</div><div class="card-body">
      <form method="post" action="<?= url('respaldos/restaurar') ?>" enctype="multipart/form-data" onsubmit="return confirm('Se reemplazarán TODOS los datos actuales. ¿Continuar?')">
        <?= csrf_field() ?>
        <div class="mb-2"><label class="form-label small">Desde un respaldo del servidor</label>
          <select name="archivo" class="form-select form-select-sm"><option value="">— o suba un archivo abajo —</option>
            <?php foreach ($rows as $r): ?><option value="<?= e($r['archivo']) ?>"><?= e($r['archivo']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-2"><label class="form-label small">Archivo .sql descargado previamente</label><input type="file" name="respaldo" accept=".sql" class="form-control form-control-sm"></div>
        <div class="mb-2"><label class="form-label small">Escriba <strong>RESTAURAR</strong> para confirmar</label><input name="confirmar" class="form-control form-control-sm" required autocomplete="off"></div>
        <button class="btn btn-danger btn-sm w-100">Restaurar base de datos</button>
        <div class="form-text">Antes de restaurar se crea automáticamente una copia del estado actual.</div>
      </form>
    </div></div>
  </div>
</div>
