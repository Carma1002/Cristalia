<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? '') ?> · <?= APP_NAME ?></title>
  <link rel="icon" href="assets/img/logo.svg" type="image/svg+xml">
  <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/app.css?v=<?= APP_VERSION ?>" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
  <div class="container py-5" style="max-width: 420px">
    <?php foreach (flashes() as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?>"><?= nl2br(e($f['msg'])) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
  </div>
</body>
</html>
