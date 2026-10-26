<div class="text-center mb-4">
  <img src="assets/img/logo.svg" width="64" height="64" alt="">
  <h1 class="h3 mt-2 mb-0"><?= APP_NAME ?></h1>
  <p class="text-muted small"><?= e(ajuste('empresa_nombre')) ?></p>
</div>
<div class="card shadow-sm">
  <div class="card-body p-4">
    <form method="post" action="<?= url('login') ?>" autocomplete="on">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="usuario">Usuario</label>
        <input class="form-control" id="usuario" name="usuario" value="<?= e(old('usuario')) ?>" required autofocus autocapitalize="none">
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Contraseña</label>
        <input class="form-control" type="password" id="password" name="password" required>
      </div>
      <button class="btn btn-primary w-100">Iniciar sesión</button>
    </form>
  </div>
</div>
<p class="text-center text-muted small mt-3">Sistema de gestión de inventario, pedidos y ventas</p>
