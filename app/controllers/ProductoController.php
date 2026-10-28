<?php
final class ProductoController extends Controller
{
    /**
     * Productos: catálogo digital (tarjetas con imagen) y control de existencias (lista),
     * con filtros por código, tipo, marca, modelo, año, sucursal y estado de stock.
     */
    public function index(): void
    {
        // La vista elegida (tarjetas o lista) se recuerda durante la sesión
        $vista = input('vista');
        if (in_array($vista, ['tarjetas', 'lista'], true)) $_SESSION['vista_productos'] = $vista;
        $vista = $_SESSION['vista_productos'] ?? 'tarjetas';
        $gestiona = can('inventario.gestionar');

        [$lim, $off, $page] = paginar($vista === 'lista' ? 30 : 24);
        $estado = (string)input('estado', '');
        $f = [
            'q' => input('q', ''), 'tipo_id' => input_int('tipo_id'), 'marca' => input('marca', ''),
            'modelo' => input('modelo', ''), 'anio' => input_int('anio'),
            'activo' => $gestiona && $estado === 'inactivo' ? '0' : '1',
            'sucursal_id' => Auth::sucursalFija() ?? input_int('sucursal_id'), 'orden' => input('orden', ''),
            'estado' => in_array($estado, ['disponible', 'bajo', 'agotado'], true) ? $estado : '',
        ];
        [$rows, $total] = Producto::buscar($f, $lim, $off);
        $conteo = Producto::contarEstados(array_merge($f, ['activo' => '1']));
        if ($gestiona) $conteo['inactivo'] = Producto::buscar(array_merge($f, ['activo' => '0', 'estado' => '']), 1, 0)[1];

        // Existencias por sucursal de los productos mostrados
        $disp = [];
        if ($rows) {
            $in = implode(',', array_map(fn($r) => (int)$r['id'], $rows));
            foreach (DB::all("SELECT e.producto_id, s.id AS sucursal_id, s.nombre, e.cantidad FROM existencias e
                              JOIN sucursales s ON s.id = e.sucursal_id AND s.activo = 1
                              WHERE e.producto_id IN ($in) ORDER BY s.nombre") as $e) {
                $disp[$e['producto_id']][$e['sucursal_id']] = $e;
            }
        }
        $this->view('productos/index', [
            'title' => 'Productos', 'rows' => $rows, 'total' => $total, 'page' => $page, 'lim' => $lim, 'f' => $f,
            'estado' => $estado, 'vista' => $vista, 'conteo' => $conteo, 'disp' => $disp,
            'tipos' => Producto::tipos(), 'marcas' => Producto::marcas(), 'sucursales' => Sucursal::delUsuario(),
        ]);
    }

    public function ver(): void
    {
        $p = $this->found(Producto::find((int)input_int('id')), 'El producto');
        $this->view('productos/ver', [
            'title' => $p['nombre'], 'p' => $p,
            'existencias' => Producto::existenciasPorSucursal((int)$p['id']),
            'movs' => Reportes::movimientos('2000-01-01', date('Y-m-d'), Auth::sucursalFija(), null, (int)$p['id'], 15),
        ]);
    }

    public function form(): void
    {
        $id = input_int('id');
        $p = $id ? $this->found(Producto::find($id), 'El producto') : null;
        $this->view('productos/form', [
            'title' => $p ? 'Editar producto' : 'Nuevo producto', 'p' => $p,
            'tipos' => Producto::tipos(), 'marcas' => Producto::marcas(), 'sucursales' => Sucursal::delUsuario(),
        ]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $id = input_int('id');
        $actual = $id ? $this->found(Producto::find($id), 'El producto') : null;

        $d = [
            'codigo'          => strtoupper((string)input('codigo', '')),
            'nombre'          => (string)input('nombre', ''),
            'tipo_id'         => (int)input_int('tipo_id', 0),
            'marca_vehiculo'  => (string)input('marca_vehiculo', '') ?: null,
            'modelo_vehiculo' => (string)input('modelo_vehiculo', '') ?: null,
            'anio_desde'      => input_int('anio_desde'),
            'anio_hasta'      => input_int('anio_hasta'),
            'descripcion'     => (string)input('descripcion', '') ?: null,
            'precio'          => input_dec('precio'),
            'costo'           => input_dec('costo'),
            'stock_minimo'    => max(0, (int)input_int('stock_minimo', 0)),
        ];
        // Validaciones
        $err = [];
        if (!preg_match('/^[A-Z0-9\-_.\/]{2,40}$/', $d['codigo'])) $err[] = 'Código inválido (2-40 caracteres: letras, números, - _ . /).';
        if (mb_strlen($d['nombre']) < 3) $err[] = 'El nombre debe tener al menos 3 caracteres.';
        if (!DB::value('SELECT id FROM tipos_producto WHERE id = ?', [$d['tipo_id']])) $err[] = 'Seleccione el tipo de producto.';
        if ($d['precio'] <= 0) $err[] = 'El precio de venta debe ser mayor que cero.';
        if ($d['costo'] < 0) $err[] = 'El costo no puede ser negativo.';
        foreach (['anio_desde', 'anio_hasta'] as $k) {
            if ($d[$k] !== null && ($d[$k] < 1950 || $d[$k] > (int)date('Y') + 2)) $err[] = 'Año fuera de rango.';
        }
        if ($d['anio_desde'] && $d['anio_hasta'] && $d['anio_desde'] > $d['anio_hasta']) $err[] = 'El año "desde" no puede ser mayor que "hasta".';
        $dup = Producto::porCodigo($d['codigo']);
        if ($dup && (int)$dup['id'] !== (int)$id) $err[] = "Ya existe un producto con el código {$d['codigo']}.";
        if ($err) throw new AppException(implode("\n", $err));

        $img = $this->subirImagen();
        if ($img) $d['imagen'] = $img;
        if ($img && $actual && $actual['imagen']) @unlink(ROOT_PATH . '/' . $actual['imagen']);
        if (input('quitar_imagen') && $actual && $actual['imagen'] && !$img) {
            @unlink(ROOT_PATH . '/' . $actual['imagen']);
            $d['imagen'] = null;
        }

        $nuevoId = DB::transaction(function () use ($d, $id, $actual) {
            $pid = Producto::guardar($d, $id);
            if (!$id) {
                // Existencia inicial por sucursal (queda en el kardex como ENTRADA)
                foreach ((array)($_POST['inicial'] ?? []) as $sid => $cant) {
                    $cant = (int)$cant;
                    if ($cant > 0 && Auth::puedeSucursal((int)$sid) && Sucursal::find((int)$sid)) {
                        InventarioService::mover($pid, (int)$sid, $cant, 'ENTRADA', 'Inventario inicial');
                    }
                }
                Bitacora::log('inventario', 'producto_crear', "{$d['codigo']} · {$d['nombre']}");
            } else {
                $cambios = [];
                foreach ($d as $k => $v) if (array_key_exists($k, $actual) && (string)$actual[$k] !== (string)$v) $cambios[] = $k;
                if (in_array('precio', $cambios, true)) $cambios[array_search('precio', $cambios)] = "precio {$actual['precio']}→{$d['precio']}";
                Bitacora::log('inventario', 'producto_editar', "{$d['codigo']} · cambios: " . (implode(', ', $cambios) ?: 'ninguno'));
            }
            return $pid;
        });
        flash('success', $id ? 'Producto actualizado.' : 'Producto creado.');
        redirect('productos/ver', ['id' => $nuevoId]);
    }

    public function estado(): void
    {
        $this->requirePost();
        $p = $this->found(Producto::find((int)input_int('id')), 'El producto');
        $nuevo = $p['activo'] ? 0 : 1;
        if (!$nuevo) {
            $abiertos = (int)DB::value("SELECT COUNT(*) FROM pedido_detalle d JOIN pedidos pe ON pe.id = d.pedido_id
                                        WHERE d.producto_id = ? AND pe.estado IN ('PENDIENTE','CONFIRMADO')", [$p['id']]);
            if ($abiertos) throw new AppException("No se puede desactivar: el producto está en $abiertos pedido(s) abierto(s).");
        }
        DB::update('productos', ['activo' => $nuevo], 'id = :id', ['id' => $p['id']]);
        Bitacora::log('inventario', $nuevo ? 'producto_activar' : 'producto_desactivar', $p['codigo']);
        flash('success', $nuevo ? 'Producto activado.' : 'Producto desactivado (ya no aparece en el catálogo ni en pedidos).');
        redirect('productos/ver', ['id' => $p['id']]);
    }

    /** Sube y valida una imagen (JPG/PNG/WEBP, máx. 2 MB). Devuelve la ruta relativa o null. */
    private function subirImagen(): ?string
    {
        $f = $_FILES['imagen'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if ($f['error'] !== UPLOAD_ERR_OK) throw new AppException('No se pudo subir la imagen (código ' . $f['error'] . ').');
        if ($f['size'] > 2 * 1024 * 1024) throw new AppException('La imagen no debe superar 2 MB.');
        $info = @getimagesize($f['tmp_name']);
        $ext = match ($info['mime'] ?? '') {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', default => null,
        };
        if (!$ext) throw new AppException('Formato de imagen no permitido (use JPG, PNG o WEBP).');
        $dir = ROOT_PATH . '/uploads/productos';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $name = date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) throw new AppException('No se pudo guardar la imagen en el servidor.');
        return "uploads/productos/$name";
    }
}
