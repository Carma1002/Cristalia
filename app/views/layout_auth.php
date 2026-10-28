<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0F536B">
  <title><?= e($title ?? '') ?> · <?= APP_NAME ?></title>
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
  <link href="assets/vendor/fonts/fonts.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/app.css?v=<?= APP_VERSION ?>" rel="stylesheet">
</head>
<body>
  <div id="c-loader"></div>
  <div class="c-auth">
    <section class="c-auth__brand">
      <img class="c-auth__logo" src="assets/img/cristalia-vertical-blanco.svg" alt="Cristalia">
      <div>
        <p class="c-auth__claim">Claridad<br>para <b>decidir.</b></p>
        <div class="c-auth__values">
          <div><b>Claridad</b><small>Datos legibles a primera vista.</small></div>
          <div><b>Precisión</b><small>Información confiable y trazable.</small></div>
          <div><b>Confianza</b><small>Decisiones con respaldo.</small></div>
        </div>
      </div>
    </section>
    <section class="c-auth__form">
      <div class="c-auth__card">
        <?php foreach (flashes() as $f): ?>
          <div class="alert alert-<?= e($f['type']) ?> reveal"><i class="bi bi-<?= $f['type'] === 'danger' ? 'exclamation-circle' : 'info-circle' ?>"></i><div><?= nl2br(e($f['msg'])) ?></div></div>
        <?php endforeach; ?>
        <?= $content ?>
      </div>
    </section>
  </div>
  <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
