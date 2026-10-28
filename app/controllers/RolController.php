<?php
/**
 * Permisos por rol.
 * Los roles son fijos (Administrador, Ventas/Facturación y Almacén); aquí sólo se decide
 * qué puede hacer cada uno, marcando casillas en una tabla. El Administrador siempre puede todo.
 */
final class RolController extends Controller
{
    /** Datos para la tabla de permisos (se muestra en la pantalla de Usuarios). */
    public static function matriz(): array
    {
        $roles = DB::all('SELECT r.*, (SELECT COUNT(*) FROM usuarios u WHERE u.rol_id = r.id) AS usuarios FROM roles r ORDER BY r.id');
        $asignados = [];
        foreach (DB::all('SELECT rol_id, permiso_id FROM rol_permiso') as $rp) $asignados[(int)$rp['rol_id']][(int)$rp['permiso_id']] = true;
        $permisos = [];
        // "Inicio" está disponible para todos, por eso su permiso no se muestra
        foreach (DB::all("SELECT * FROM permisos WHERE clave <> 'dashboard.ver' ORDER BY id") as $p) $permisos[$p['modulo']][] = $p;
        return [$roles, $permisos, $asignados];
    }

    public function guardar(): void
    {
        $this->requirePost();
        $validos = array_map('intval', array_column(DB::all('SELECT id FROM permisos'), 'id'));
        $enviado = (array)($_POST['permisos'] ?? []);
        $roles = DB::all('SELECT * FROM roles WHERE es_sistema = 0 AND id IN (' . implode(',', array_map('intval', (array)($_POST['roles'] ?? [0]))) . ')');
        DB::transaction(function () use ($roles, $enviado, $validos) {
            foreach ($roles as $rol) {
                $perms = array_values(array_intersect(array_map('intval', (array)($enviado[$rol['id']] ?? [])), $validos));
                $panel = (int)DB::value("SELECT id FROM permisos WHERE clave = 'dashboard.ver'");
                if ($panel && !in_array($panel, $perms, true)) $perms[] = $panel;
                DB::run('DELETE FROM rol_permiso WHERE rol_id = ?', [$rol['id']]);
                foreach ($perms as $p) DB::insert('rol_permiso', ['rol_id' => $rol['id'], 'permiso_id' => $p]);
                Bitacora::log('seguridad', 'permisos', "{$rol['nombre']}: " . count($perms) . ' permisos');
            }
        });
        flash('success', 'Permisos guardados. Aplican de inmediato a los usuarios de cada rol.');
        redirect('usuarios', ['tab' => 'permisos']);
    }
}
