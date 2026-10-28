<?php $logo = logo_cliente(); ?>
<div class="c-auth__client">
  <?php if ($logo): ?><img src="<?= e($logo) ?>" alt=""><?php endif; ?>
  <div class="min-w-0">
    <div class="c-eyebrow">Acceso al sistema</div>
    <div class="fw-semibold text-truncate"><?= e(ajuste('empresa_nombre')) ?></div>
  </div>
</div>
<h1>Bienvenido</h1>
<p class="text-muted mb-4">Ingrese con el usuario y la contraseña que le asignó el administrador.</p>
<form method="post" action="<?= url('login') ?>" autocomplete="on">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label" for="usuario">Usuario</label>
    <div class="c-search"><i class="bi bi-person"></i>
      <input class="form-control form-control-lg" id="usuario" name="usuario" value="<?= e(old('usuario')) ?>" required autofocus autocapitalize="none" placeholder="ej. maria.rodriguez">
    </div>
  </div>
  <div class="mb-4">
    <label class="form-label" for="password">Contraseña</label>
    <div class="c-search c-pw"><i class="bi bi-lock"></i>
      <input class="form-control form-control-lg" type="password" id="password" name="password" required placeholder="••••••••">
      <button type="button" data-toggle-pw aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
    </div>
  </div>
  <button class="btn btn-primary btn-lg w-100">Entrar <i class="bi bi-arrow-right"></i></button>
</form>
<div class="c-auth__foot"><img src="assets/img/cristalia-isotipo.svg" alt=""> Cristalia · Sistema de información para la toma de decisiones</div>
