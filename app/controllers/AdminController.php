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

    /** Empresa y sucursales: datos del cliente, logo, sucursales y tipos de producto. */
    public function empresa(): void
    {
        $this->view('admin/empresa', [
            'title' => 'Empresa y sucursales',
            'tipos' => DB::all('SELECT t.*, (SELECT COUNT(*) FROM productos p WHERE p.tipo_id = t.id) AS productos FROM tipos_producto t ORDER BY nombre'),
            'sucursales' => DB::all("SELECT s.*,
                (SELECT COUNT(*) FROM usuarios u WHERE u.sucursal_id = s.id AND u.activo = 1) AS usuarios,
                (SELECT COALESCE(SUM(e.cantidad),0) FROM existencias e WHERE e.sucursal_id = s.id) AS unidades,
                (SELECT COUNT(*) FROM pedidos p WHERE p.sucursal_id = s.id AND p.estado IN ('PENDIENTE','CONFIRMADO')) AS pedidos_abiertos,
                (SELECT COALESCE(SUM(v.total),0) FROM ventas v WHERE v.sucursal_id = s.id AND v.estado = 'ACTIVA' AND v.fecha >= :ini) AS ventas_mes
                FROM sucursales s ORDER BY s.activo DESC, s.nombre", ['ini' => date('Y-m-01 00:00:00')]),
        ]);
    }

    public function guardarConfiguracion(): void
    {
        $this->requirePost();
        $datos = [];
        foreach (['empresa_nombre' => true, 'empresa_rif' => true, 'empresa_direccion' => false, 'moneda' => true] as $k => $obligatorio) {
            $v = mb_substr((string)input($k, ''), 0, 255);
            if ($obligatorio && $v === '') throw new AppException('Complete el nombre, el RIF y el símbolo de moneda.');
            $datos[$k] = $v;
        }
        $logo = $this->subirLogo();
        if ($logo) $datos['empresa_logo'] = $logo;
        elseif (input('quitar_logo')) $datos['empresa_logo'] = '';
        $anterior = ajuste('empresa_logo');
        foreach ($datos as $k => $v) self::guardarAjuste($k, $v);
        if (array_key_exists('empresa_logo', $datos) && $anterior && str_starts_with($anterior, 'uploads/empresa/')) @unlink(ROOT_PATH . '/' . $anterior);
        Bitacora::log('administracion', 'configuracion', 'Datos de la empresa actualizados' . ($logo ? ' (nuevo logo)' : ''));
        flash('success', 'Datos de la empresa guardados.');
        redirect('empresa');
    }

    /** Inserta o actualiza un valor de la tabla de configuración (sin cambiar la estructura de la BD). */
    public static function guardarAjuste(string $clave, string $valor): void
    {
        if (DB::value('SELECT COUNT(*) FROM configuracion WHERE clave = ?', [$clave])) {
            DB::run('UPDATE configuracion SET valor = ? WHERE clave = ?', [$valor, $clave]);
        } else {
            DB::insert('configuracion', ['clave' => $clave, 'valor' => $valor]);
        }
    }

    private function subirLogo(): ?string
    {
        $f = $_FILES['logo'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if ($f['error'] !== UPLOAD_ERR_OK) throw new AppException('No se pudo subir el logo (código ' . $f['error'] . ').');
        if ($f['size'] > 1024 * 1024) throw new AppException('El logo no debe superar 1 MB.');
        $ext = match (@getimagesize($f['tmp_name'])['mime'] ?? '') { 'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', default => null };
        if (!$ext) throw new AppException('El logo debe ser una imagen PNG, JPG o WEBP.');
        $dir = ROOT_PATH . '/uploads/empresa';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $name = 'logo_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) throw new AppException('No se pudo guardar el logo en el servidor.');
        return "uploads/empresa/$name";
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
            redirect('empresa');
        }
        if (mb_strlen($nombre) < 3) throw new AppException('Indique el nombre del tipo de producto.');
        if (DB::value('SELECT id FROM tipos_producto WHERE nombre = ? AND id <> ?', [$nombre, $id ?? 0])) throw new AppException('Ese tipo ya existe.');
        if ($id) DB::update('tipos_producto', ['nombre' => $nombre], 'id = :id', ['id' => $id]);
        else DB::insert('tipos_producto', ['nombre' => $nombre]);
        Bitacora::log('administracion', 'tipo_guardar', $nombre);
        flash('success', 'Tipo de producto guardado.');
        redirect('empresa');
    }
}
