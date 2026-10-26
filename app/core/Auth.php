<?php
/**
 * Autenticación, sesión del usuario y control de permisos por rol.
 */
final class Auth
{
    private static ?array $user = null;
    private static array $perms = [];

    const MAX_INTENTOS = 5;
    const MINUTOS_BLOQUEO = 10;
    const MINUTOS_INACTIVIDAD = 120;

    /** Intenta iniciar sesión. Devuelve null si fue exitoso o un mensaje de error. */
    public static function attempt(string $usuario, string $password): ?string
    {
        $u = DB::one('SELECT * FROM usuarios WHERE usuario = ?', [$usuario]);
        if (!$u) {
            Bitacora::log('seguridad', 'login_fallido', "Usuario inexistente: $usuario", null);
            return 'Usuario o contraseña incorrectos.';
        }
        if (!$u['activo']) return 'El usuario está desactivado. Contacte al administrador.';
        if ($u['bloqueado_hasta'] && strtotime($u['bloqueado_hasta']) > time()) {
            $min = (int)ceil((strtotime($u['bloqueado_hasta']) - time()) / 60);
            return "Usuario bloqueado temporalmente por intentos fallidos. Intente en $min min.";
        }
        if (!password_verify($password, $u['password_hash'])) {
            $intentos = (int)$u['intentos_fallidos'] + 1;
            $bloqueo = $intentos >= self::MAX_INTENTOS
                ? date('Y-m-d H:i:s', time() + self::MINUTOS_BLOQUEO * 60) : null;
            DB::update('usuarios', [
                'intentos_fallidos' => $bloqueo ? 0 : $intentos,
                'bloqueado_hasta'   => $bloqueo,
            ], 'id = :id', ['id' => $u['id']]);
            Bitacora::log('seguridad', 'login_fallido', "Contraseña incorrecta ($intentos)", (int)$u['id']);
            return $bloqueo
                ? 'Demasiados intentos. El usuario quedó bloqueado ' . self::MINUTOS_BLOQUEO . ' minutos.'
                : 'Usuario o contraseña incorrectos.';
        }
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            DB::update('usuarios', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $u['id']]);
        }
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        $_SESSION['last_seen'] = time();
        DB::update('usuarios', ['intentos_fallidos' => 0, 'bloqueado_hasta' => null, 'ultimo_acceso' => date('Y-m-d H:i:s')],
            'id = :id', ['id' => $u['id']]);
        Bitacora::log('seguridad', 'login', 'Inicio de sesión', (int)$u['id']);
        return null;
    }

    public static function logout(): void
    {
        if (self::check()) Bitacora::log('seguridad', 'logout', 'Cierre de sesión');
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Carga el usuario actual (una vez por petición) y valida inactividad. */
    public static function check(): bool
    {
        if (self::$user !== null) return true;
        if (empty($_SESSION['uid'])) return false;
        if (time() - ($_SESSION['last_seen'] ?? 0) > self::MINUTOS_INACTIVIDAD * 60) {
            $_SESSION = [];
            return false;
        }
        $_SESSION['last_seen'] = time();
        $u = DB::one('SELECT u.*, r.nombre AS rol_nombre, r.es_sistema AS rol_admin, s.nombre AS sucursal_nombre
                      FROM usuarios u JOIN roles r ON r.id = u.rol_id
                      LEFT JOIN sucursales s ON s.id = u.sucursal_id
                      WHERE u.id = ? AND u.activo = 1', [$_SESSION['uid']]);
        if (!$u) { $_SESSION = []; return false; }
        self::$user = $u;
        self::$perms = $u['rol_admin']
            ? array_column(DB::all('SELECT clave FROM permisos'), 'clave')
            : array_column(DB::all('SELECT p.clave FROM rol_permiso rp JOIN permisos p ON p.id = rp.permiso_id WHERE rp.rol_id = ?', [$u['rol_id']]), 'clave');
        return true;
    }

    public static function user(): ?array { return self::check() ? self::$user : null; }
    public static function id(): ?int { return self::check() ? (int)self::$user['id'] : null; }
    public static function isAdmin(): bool { return self::check() && (bool)self::$user['rol_admin']; }

    public static function can(string $perm): bool
    {
        return self::check() && in_array($perm, self::$perms, true);
    }

    /**
     * Sucursal a la que está limitado el usuario.
     * Los administradores o usuarios sin sucursal asignada operan en todas (null).
     */
    public static function sucursalFija(): ?int
    {
        if (!self::check() || self::isAdmin()) return null;
        return self::$user['sucursal_id'] ? (int)self::$user['sucursal_id'] : null;
    }

    /** Verifica que el usuario pueda operar sobre una sucursal concreta. */
    public static function puedeSucursal(int $sucursalId): bool
    {
        $f = self::sucursalFija();
        return $f === null || $f === $sucursalId;
    }
}
