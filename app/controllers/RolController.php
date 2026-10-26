<?php
final class RolController extends Controller
{
    public function index(): void
    {
        $roles = DB::all('SELECT r.*, (SELECT COUNT(*) FROM usuarios u WHERE u.rol_id = r.id) AS usuarios,
                                 (SELECT COUNT(*) FROM rol_permiso rp WHERE rp.rol_id = r.id) AS permisos
                          FROM roles r ORDER BY r.id');
        $this->view('admin/roles', ['title' => 'Roles y permisos', 'roles' => $roles]);
    }

    public function form(): void
    {
        $id = input_int('id');
        $rol = $id ? $this->found(DB::one('SELECT * FROM roles WHERE id = ?', [$id]), 'El rol') : null;
        $asignados = $id ? array_map('intval', array_column(DB::all('SELECT permiso_id FROM rol_permiso WHERE rol_id = ?', [$id]), 'permiso_id')) : [];
        $permisos = [];
        foreach (DB::all('SELECT * FROM permisos ORDER BY id') as $p) $permisos[$p['modulo']][] = $p;
        $this->view('admin/rol_form', ['title' => $rol ? 'Editar rol' : 'Nuevo rol', 'rol' => $rol, 'permisos' => $permisos, 'asignados' => $asignados]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $id = input_int('id');
        $rol = $id ? $this->found(DB::one('SELECT * FROM roles WHERE id = ?', [$id]), 'El rol') : null;
        $nombre = (string)input('nombre', '');
        if (mb_strlen($nombre) < 3) throw new AppException('Indique el nombre del rol.');
        if (DB::value('SELECT id FROM roles WHERE nombre = ? AND id <> ?', [$nombre, $id ?? 0])) throw new AppException('Ya existe un rol con ese nombre.');
        $perms = array_map('intval', (array)($_POST['permisos'] ?? []));
        $validos = array_map('intval', array_column(DB::all('SELECT id FROM permisos'), 'id'));
        $perms = array_values(array_intersect($perms, $validos));

        DB::transaction(function () use (&$id, $rol, $nombre, $perms) {
            $d = ['nombre' => $nombre, 'descripcion' => (string)input('descripcion', '') ?: null];
            if ($id) DB::update('roles', $d, 'id = :id', ['id' => $id]);
            else $id = DB::insert('roles', $d);
            // El rol Administrador (de sistema) siempre conserva todos los permisos
            if (!$rol || !$rol['es_sistema']) {
                DB::run('DELETE FROM rol_permiso WHERE rol_id = ?', [$id]);
                foreach ($perms as $p) DB::insert('rol_permiso', ['rol_id' => $id, 'permiso_id' => $p]);
            }
            Bitacora::log('seguridad', $rol ? 'rol_editar' : 'rol_crear', "$nombre · " . count($perms) . ' permisos');
        });
        flash('success', 'Rol guardado. Los cambios aplican de inmediato a sus usuarios.');
        redirect('roles');
    }

    public function eliminar(): void
    {
        $this->requirePost();
        $rol = $this->found(DB::one('SELECT * FROM roles WHERE id = ?', [(int)input_int('id')]), 'El rol');
        if ($rol['es_sistema']) throw new AppException('El rol Administrador no puede eliminarse.');
        if (DB::value('SELECT COUNT(*) FROM usuarios WHERE rol_id = ?', [$rol['id']])) throw new AppException('El rol tiene usuarios asignados.');
        DB::run('DELETE FROM roles WHERE id = ?', [$rol['id']]);
        Bitacora::log('seguridad', 'rol_eliminar', $rol['nombre']);
        flash('success', 'Rol eliminado.');
        redirect('roles');
    }
}
