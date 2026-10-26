<?php
final class UsuarioController extends Controller
{
    public static function validarPassword(string $p, string $conf): void
    {
        if (strlen($p) < 8 || !preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) {
            throw new AppException('La contraseña debe tener al menos 8 caracteres e incluir letras y números.');
        }
        if ($p !== $conf) throw new AppException('La confirmación no coincide con la contraseña.');
    }

    public function index(): void
    {
        $rows = DB::all('SELECT u.*, r.nombre AS rol, s.nombre AS sucursal FROM usuarios u
                         JOIN roles r ON r.id = u.rol_id LEFT JOIN sucursales s ON s.id = u.sucursal_id ORDER BY u.nombre');
        $this->view('admin/usuarios', ['title' => 'Usuarios', 'rows' => $rows]);
    }

    public function form(): void
    {
        $id = input_int('id');
        $u = $id ? $this->found(DB::one('SELECT * FROM usuarios WHERE id = ?', [$id]), 'El usuario') : null;
        $this->view('admin/usuario_form', [
            'title' => $u ? 'Editar usuario' : 'Nuevo usuario', 'u' => $u,
            'roles' => DB::all('SELECT * FROM roles ORDER BY id'), 'sucursales' => Sucursal::todas(),
        ]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $id = input_int('id');
        $actual = $id ? $this->found(DB::one('SELECT * FROM usuarios WHERE id = ?', [$id]), 'El usuario') : null;
        $d = [
            'nombre'      => (string)input('nombre', ''),
            'usuario'     => strtolower((string)input('usuario', '')),
            'email'       => (string)input('email', '') ?: null,
            'rol_id'      => (int)input_int('rol_id', 0),
            'sucursal_id' => input_int('sucursal_id'),
        ];
        $err = [];
        if (mb_strlen($d['nombre']) < 3) $err[] = 'Indique el nombre completo.';
        if (!preg_match('/^[a-z0-9._-]{3,40}$/', $d['usuario'])) $err[] = 'Usuario inválido (3-40: letras minúsculas, números, . _ -).';
        if ($d['email'] && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $err[] = 'Correo inválido.';
        $rol = DB::one('SELECT * FROM roles WHERE id = ?', [$d['rol_id']]);
        if (!$rol) $err[] = 'Seleccione un rol.';
        if ($d['sucursal_id'] && !Sucursal::find($d['sucursal_id'])) $err[] = 'Sucursal inválida.';
        if ($rol && !$rol['es_sistema'] && !$d['sucursal_id']) $err[] = 'Los usuarios que no son administradores deben tener una sucursal asignada.';
        if (DB::value('SELECT id FROM usuarios WHERE usuario = ? AND id <> ?', [$d['usuario'], $id ?? 0])) $err[] = 'El nombre de usuario ya existe.';
        // Evitar quedarse sin administradores
        if ($actual && $this->esUltimoAdmin((int)$actual['id']) && $rol && !$rol['es_sistema']) {
            $err[] = 'No puede quitar el rol de administrador al único administrador activo.';
        }
        $pass = (string)($_POST['password'] ?? '');
        if (!$id || $pass !== '') {
            try { self::validarPassword($pass, (string)($_POST['password2'] ?? '')); } catch (AppException $e) { $err[] = $e->getMessage(); }
        }
        if ($err) throw new AppException(implode("\n", $err));

        if ($pass !== '') $d['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
        if ($id) {
            DB::update('usuarios', $d, 'id = :id', ['id' => $id]);
            Bitacora::log('seguridad', 'usuario_editar', "{$d['usuario']} · rol {$rol['nombre']}" . ($pass ? ' · contraseña restablecida' : ''));
        } else {
            $d['creado_en'] = date('Y-m-d H:i:s');
            $id = DB::insert('usuarios', $d);
            Bitacora::log('seguridad', 'usuario_crear', "{$d['usuario']} · rol {$rol['nombre']}");
        }
        flash('success', 'Usuario guardado.');
        redirect('usuarios');
    }

    public function estado(): void
    {
        $this->requirePost();
        $u = $this->found(DB::one('SELECT * FROM usuarios WHERE id = ?', [(int)input_int('id')]), 'El usuario');
        if ((int)$u['id'] === Auth::id()) throw new AppException('No puede desactivar su propio usuario.');
        if ($u['activo'] && $this->esUltimoAdmin((int)$u['id'])) throw new AppException('No puede desactivar al único administrador.');
        DB::update('usuarios', ['activo' => $u['activo'] ? 0 : 1, 'intentos_fallidos' => 0, 'bloqueado_hasta' => null], 'id = :id', ['id' => $u['id']]);
        Bitacora::log('seguridad', $u['activo'] ? 'usuario_desactivar' : 'usuario_activar', $u['usuario']);
        flash('success', $u['activo'] ? 'Usuario desactivado.' : 'Usuario activado.');
        redirect('usuarios');
    }

    private function esUltimoAdmin(int $id): bool
    {
        $admins = DB::all('SELECT u.id FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE r.es_sistema = 1 AND u.activo = 1');
        return count($admins) === 1 && (int)$admins[0]['id'] === $id;
    }
}
