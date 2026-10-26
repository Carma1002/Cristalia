<?php
/**
 * Cristalia · Instalador web
 * Crea las tablas, datos base, sucursal principal y usuario administrador,
 * y genera app/config.php. Se bloquea automáticamente una vez instalado.
 */
require __DIR__ . '/_verificar.php';
require __DIR__ . '/app/bootstrap.php';

$errores = [];
$listo = false;
$configTexto = null;
$claveDemo = null;

if (APP_INSTALLED) {
    $bloqueado = true;
} else {
    $bloqueado = false;
    $req = [
        'PHP 8.1 o superior (actual: ' . PHP_VERSION . ')' => PHP_VERSION_ID >= 80100,
        'Extensión PDO MySQL' => extension_loaded('pdo_mysql'),
        'Extensión mbstring' => extension_loaded('mbstring'),
        'Carpeta app/ con permiso de escritura' => is_writable(APP_PATH),
        'Carpeta uploads/ con permiso de escritura' => is_writable(ROOT_PATH . '/uploads') || @mkdir(ROOT_PATH . '/uploads', 0755, true),
    ];

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $db = [
            'host' => trim($_POST['db_host'] ?? ''), 'port' => (int)($_POST['db_port'] ?? 3306),
            'name' => trim($_POST['db_name'] ?? ''), 'user' => trim($_POST['db_user'] ?? ''), 'pass' => (string)($_POST['db_pass'] ?? ''),
        ];
        $adminNombre = trim($_POST['admin_nombre'] ?? '');
        $adminUser = strtolower(trim($_POST['admin_usuario'] ?? ''));
        $adminPass = (string)($_POST['admin_password'] ?? '');
        $empresa = trim($_POST['empresa'] ?? '');
        $sucursal = trim($_POST['sucursal'] ?? '');
        $demo = !empty($_POST['demo']);

        if (!$db['host'] || !$db['name'] || !$db['user']) $errores[] = 'Complete los datos de conexión a la base de datos.';
        if (mb_strlen($adminNombre) < 3) $errores[] = 'Indique el nombre del administrador.';
        if (!preg_match('/^[a-z0-9._-]{3,40}$/', $adminUser)) $errores[] = 'Usuario administrador inválido.';
        if (strlen($adminPass) < 8 || !preg_match('/[A-Za-z]/', $adminPass) || !preg_match('/\d/', $adminPass)) $errores[] = 'La contraseña del administrador debe tener 8+ caracteres con letras y números.';
        if (!$empresa || !$sucursal) $errores[] = 'Indique la empresa y la sucursal principal.';

        if (!$errores) {
            try {
                $GLOBALS['config'] = ['db' => $db];
                // En localhost (XAMPP) la base se crea sola si no existe; en InfinityFree ya viene creada
                if (!preg_match('/^[A-Za-z0-9_]+$/', $db['name'])) throw new RuntimeException('El nombre de la base solo puede tener letras, números y guion bajo.');
                try {
                    $srv = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $db['host'], $db['port']), $db['user'], $db['pass'],
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    $srv->exec("CREATE DATABASE IF NOT EXISTS `{$db['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                } catch (PDOException) {
                    // Sin permiso para crear bases (hosting compartido): se usa la existente
                }
                $pdo = DB::connect($db);
                DB::setPdo($pdo);
                foreach (['schema.sql', 'seed.sql'] as $f) {
                    foreach (Respaldo::dividir(file_get_contents(ROOT_PATH . "/database/$f")) as $sql) $pdo->exec($sql);
                }
                DB::run("UPDATE configuracion SET valor = ? WHERE clave = 'empresa_nombre'", [$empresa]);
                $sid = DB::insert('sucursales', ['codigo' => 'PRI', 'nombre' => $sucursal, 'direccion' => null]);
                $uid = DB::insert('usuarios', ['nombre' => $adminNombre, 'usuario' => $adminUser,
                    'password_hash' => password_hash($adminPass, PASSWORD_DEFAULT), 'rol_id' => 1, 'sucursal_id' => null]);
                $_SESSION['uid'] = $uid;
                $_SESSION['last_seen'] = time();
                Bitacora::log('administracion', 'instalacion', 'Sistema instalado' . ($demo ? ' con datos de demostración' : ''), $uid);
                if ($demo) {
                    @set_time_limit(300);
                    $claveDemo = bin2hex(random_bytes(6));
                    require ROOT_PATH . '/database/demo.php';
                    cargar_demo($claveDemo);
                }
                $configTexto = "<?php\n// Generado por el instalador de " . APP_NAME . ' el ' . date('Y-m-d H:i') . "\n// NO subir este archivo a GitHub (contiene credenciales).\nreturn " . var_export([
                    'db' => $db, 'timezone' => 'America/Caracas', 'debug' => false,
                ], true) . ";\n";
                if (@file_put_contents(APP_PATH . '/config.php', $configTexto) !== false) $configTexto = null;
                $_SESSION = [];
                $listo = true;
            } catch (Throwable $e) {
                $errores[] = 'Error durante la instalación: ' . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Instalación · <?= APP_NAME ?></title>
  <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
<div class="container py-4" style="max-width: 760px">
  <div class="text-center mb-4"><img src="assets/img/logo.svg" width="56" alt=""><h1 class="h3 mt-2">Instalación de <?= APP_NAME ?></h1></div>

  <?php if ($bloqueado): ?>
    <div class="alert alert-success">El sistema ya está instalado. Por seguridad el instalador está bloqueado.<br>
      Para reinstalar, elimine el archivo <code>app/config.php</code> del servidor.</div>
    <a class="btn btn-primary" href="index.php">Ir al sistema</a>

  <?php elseif ($listo): ?>
    <div class="alert alert-success"><strong>¡Instalación completada!</strong></div>
    <?php if ($configTexto): ?>
      <div class="alert alert-warning">No se pudo escribir <code>app/config.php</code> automáticamente. Cree ese archivo con este contenido usando el administrador de archivos del hosting:</div>
      <pre class="bg-dark text-light p-3 rounded small"><?= htmlspecialchars($configTexto) ?></pre>
    <?php endif; ?>
    <?php if (!empty($demo)): ?>
      <div class="alert alert-info">Se cargaron datos de demostración. La contraseña temporal para estos usuarios es <code><?= htmlspecialchars((string)$claveDemo, ENT_QUOTES, 'UTF-8') ?></code>. Anótala ahora; se genera aleatoriamente para esta instalación. Usuarios: <code>ventas</code>, <code>almacen</code>, <code>ventas.val</code> y <code>almacen.mcy</code>.</div>
    <?php endif; ?>
    <a class="btn btn-primary" href="index.php">Iniciar sesión</a>

  <?php else: ?>
    <div class="card mb-3"><div class="card-body">
      <h2 class="h6">Requisitos del servidor</h2>
      <ul class="list-unstyled mb-0 small">
        <?php foreach ($req as $k => $ok): ?><li><?= $ok ? '✅' : '❌' ?> <?= $k ?></li><?php endforeach; ?>
      </ul>
    </div></div>
    <?php foreach ($errores as $e): ?><div class="alert alert-danger"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    <form method="post" class="card card-body">
      <h2 class="h6">1. Base de datos MySQL</h2>
      <p class="small text-muted"><strong>En localhost (XAMPP):</strong> host <code>localhost</code>, usuario <code>root</code>, contraseña vacía y nombre <code>cristalia</code> (se crea automáticamente).<br><strong>En InfinityFree:</strong> Panel de control → <em>MySQL Databases</em>. Allí aparecen el host (ej. <code>sql123.infinityfree.com</code>), el nombre (ej. <code>if0_12345678_cristalia</code>), el usuario y la contraseña.</p>
      <div class="row g-2 mb-3">
        <div class="col-8"><label class="form-label small">Host</label><input name="db_host" class="form-control" required value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>"></div>
        <div class="col-4"><label class="form-label small">Puerto</label><input name="db_port" class="form-control" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>"></div>
        <div class="col-md-4"><label class="form-label small">Nombre de la base</label><input name="db_name" class="form-control" required value="<?= htmlspecialchars($_POST['db_name'] ?? 'cristalia') ?>"></div>
        <div class="col-md-4"><label class="form-label small">Usuario</label><input name="db_user" class="form-control" required value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>"></div>
        <div class="col-md-4"><label class="form-label small">Contraseña</label><input name="db_pass" type="password" class="form-control"></div>
      </div>
      <h2 class="h6">2. Empresa</h2>
      <div class="row g-2 mb-3">
        <div class="col-md-7"><label class="form-label small">Razón social</label><input name="empresa" class="form-control" required value="<?= htmlspecialchars($_POST['empresa'] ?? 'Empresa de autopartes') ?>"></div>
        <div class="col-md-5"><label class="form-label small">Sucursal principal</label><input name="sucursal" class="form-control" required value="<?= htmlspecialchars($_POST['sucursal'] ?? 'Sucursal principal') ?>"></div>
      </div>
      <h2 class="h6">3. Usuario administrador</h2>
      <div class="row g-2 mb-3">
        <div class="col-md-4"><label class="form-label small">Nombre</label><input name="admin_nombre" class="form-control" required value="<?= htmlspecialchars($_POST['admin_nombre'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label small">Usuario</label><input name="admin_usuario" class="form-control" required value="<?= htmlspecialchars($_POST['admin_usuario'] ?? 'admin') ?>"></div>
        <div class="col-md-4"><label class="form-label small">Contraseña</label><input name="admin_password" type="password" class="form-control" required minlength="8"></div>
      </div>
      <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="demo" value="1" id="demo" <?= !empty($_POST['demo']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="demo">Cargar datos de demostración (3 sucursales, 24 productos, 12 meses de ventas). Útil para la presentación.</label></div>
      <div class="alert alert-warning small py-2">Atención: si la base de datos ya tiene tablas de <?= APP_NAME ?>, serán reemplazadas.</div>
      <button class="btn btn-primary">Instalar</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
