<?php
final class AdminController extends Controller
{
    public function bitacora(): void
    {
        [$lim, $off, $page] = paginar(50);
        $w = ['1=1'];
        $p = [];
        if ($m = input('modulo'))   { $w[] = 'b.modulo = :m'; $p['m'] = $m; }
        if ($u = input_int('usuario_id')) { $w[] = 'b.usuario_id = :u'; $p['u'] = $u; }
        if ($d = input('desde'))    { $w[] = 'b.fecha >= :d'; $p['d'] = "$d 00:00:00"; }
        if ($h = input('hasta'))    { $w[] = 'b.fecha <= :h'; $p['h'] = "$h 23:59:59"; }
        if ($q = input('q'))        { $w[] = '(b.accion LIKE :q1 OR b.detalle LIKE :q2)'; $p['q1'] = $p['q2'] = "%$q%"; }
        $ws = implode(' AND ', $w);
        $total = (int)DB::value("SELECT COUNT(*) FROM bitacora b WHERE $ws", $p);
        $rows = DB::all("SELECT b.*, u.nombre AS usuario FROM bitacora b LEFT JOIN usuarios u ON u.id = b.usuario_id
                         WHERE $ws ORDER BY b.id DESC LIMIT :lim OFFSET :off", array_merge($p, ['lim' => $lim, 'off' => $off]));
        $this->view('admin/bitacora', [
            'title' => 'Bitácora de operaciones', 'rows' => $rows, 'total' => $total, 'page' => $page, 'lim' => $lim,
            'modulos' => array_column(DB::all('SELECT DISTINCT modulo FROM bitacora ORDER BY modulo'), 'modulo'),
            'usuarios' => DB::all('SELECT id, nombre FROM usuarios ORDER BY nombre'),
        ]);
    }

    public function respaldos(): void
    {
        $this->view('admin/respaldos', ['title' => 'Copias de seguridad', 'rows' => Respaldo::listar()]);
    }

    public function generarRespaldo(): void
    {
        $this->requirePost();
        $f = Respaldo::generar('manual');
        Bitacora::log('administracion', 'respaldo_generar', $f);
        flash('success', "Respaldo generado: $f. Descárguelo y guárdelo fuera del servidor.");
        redirect('respaldos');
    }

    public function descargarRespaldo(): void
    {
        $ruta = Respaldo::ruta((string)input('archivo', ''));
        Bitacora::log('administracion', 'respaldo_descargar', basename($ruta));
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($ruta) . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }

    public function eliminarRespaldo(): void
    {
        $this->requirePost();
        $ruta = Respaldo::ruta((string)input('archivo', ''));
        unlink($ruta);
        Bitacora::log('administracion', 'respaldo_eliminar', basename($ruta));
        flash('success', 'Respaldo eliminado del servidor.');
        redirect('respaldos');
    }

    /** Restaura desde un respaldo del servidor o desde un archivo subido. */
    public function restaurar(): void
    {
        $this->requirePost();
        if (input('confirmar') !== 'RESTAURAR') throw new AppException('Escriba RESTAURAR para confirmar la operación.');
        $archivo = (string)input('archivo', '');
        if ($archivo !== '') {
            $ruta = Respaldo::ruta($archivo);
        } else {
            $f = $_FILES['respaldo'] ?? null;
            if (!$f || $f['error'] !== UPLOAD_ERR_OK) throw new AppException('Seleccione el archivo .sql a restaurar.');
            if (!str_ends_with(strtolower($f['name']), '.sql')) throw new AppException('El archivo debe tener extensión .sql');
            $ruta = $f['tmp_name'];
        }
        $usuario = Auth::user()['usuario'];
        // Respaldo automático del estado actual antes de restaurar
        $previo = Respaldo::generar('prerestauracion');
        $n = Respaldo::restaurar($ruta);
        Bitacora::log('administracion', 'respaldo_restaurar', "Restaurado por $usuario (" . ($archivo ?: 'archivo subido') . "), $n sentencias. Copia previa: $previo");
        flash('success', "Base de datos restaurada ($n sentencias). Se guardó una copia previa: $previo");
        if (!Auth::check()) { flash('info', 'Vuelva a iniciar sesión.'); redirect('login'); }
        redirect('respaldos');
    }

    public function configuracion(): void
    {
        $this->view('admin/configuracion', [
            'title' => 'Configuración',
            'tipos' => DB::all('SELECT t.*, (SELECT COUNT(*) FROM productos p WHERE p.tipo_id = t.id) AS productos FROM tipos_producto t ORDER BY nombre'),
        ]);
    }

    public function guardarConfiguracion(): void
    {
        $this->requirePost();
        foreach (['empresa_nombre', 'empresa_rif', 'empresa_direccion', 'moneda'] as $k) {
            $v = mb_substr((string)input($k, ''), 0, 255);
            if ($v === '') throw new AppException('Complete todos los campos.');
            DB::run('UPDATE configuracion SET valor = ? WHERE clave = ?', [$v, $k]);
        }
        Bitacora::log('administracion', 'configuracion', 'Datos de la empresa actualizados');
        flash('success', 'Configuración guardada.');
        redirect('configuracion');
    }

    public function guardarTipo(): void
    {
        $this->requirePost();
        $id = input_int('id');
        $nombre = (string)input('nombre', '');
        if ($id && input('eliminar')) {
            if (DB::value('SELECT COUNT(*) FROM productos WHERE tipo_id = ?', [$id])) throw new AppException('El tipo tiene productos asociados.');
            DB::run('DELETE FROM tipos_producto WHERE id = ?', [$id]);
            Bitacora::log('administracion', 'tipo_eliminar', "#$id");
            flash('success', 'Tipo eliminado.');
            redirect('configuracion');
        }
        if (mb_strlen($nombre) < 3) throw new AppException('Indique el nombre del tipo de producto.');
        if (DB::value('SELECT id FROM tipos_producto WHERE nombre = ? AND id <> ?', [$nombre, $id ?? 0])) throw new AppException('Ese tipo ya existe.');
        if ($id) DB::update('tipos_producto', ['nombre' => $nombre], 'id = :id', ['id' => $id]);
        else DB::insert('tipos_producto', ['nombre' => $nombre]);
        Bitacora::log('administracion', 'tipo_guardar', $nombre);
        flash('success', 'Tipo de producto guardado.');
        redirect('configuracion');
    }
}
