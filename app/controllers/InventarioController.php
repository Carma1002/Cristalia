<?php
final class InventarioController extends Controller
{
    /** Matriz de existencias: productos × sucursales. */
    public function existencias(): void
    {
        [$lim, $off, $page] = paginar(30);
        $f = ['q' => input('q', ''), 'tipo_id' => input_int('tipo_id'), 'activo' => '1'];
        [$rows, $total] = Producto::buscar($f, $lim, $off);
        $sucursales = Sucursal::delUsuario();
        $ids = array_column($rows, 'id') ?: [0];
        $mapa = [];
        $in = implode(',', array_map('intval', $ids));
        foreach (DB::all("SELECT producto_id, sucursal_id, cantidad FROM existencias WHERE producto_id IN ($in)") as $e) {
            $mapa[$e['producto_id']][$e['sucursal_id']] = (int)$e['cantidad'];
        }
        $this->view('inventario/existencias', [
            'title' => 'Existencias por sucursal', 'rows' => $rows, 'total' => $total, 'page' => $page, 'lim' => $lim,
            'f' => $f, 'sucursales' => $sucursales, 'mapa' => $mapa, 'tipos' => Producto::tipos(),
        ]);
    }

    public function stockBajo(): void
    {
        $suc = $this->sucursalFiltro();
        $this->view('inventario/stock_bajo', [
            'title' => 'Alertas de stock mínimo', 'rows' => Producto::stockBajo($suc),
            'sucursales' => Sucursal::delUsuario(), 'suc' => $suc,
        ]);
    }

    public function movimientos(): void
    {
        $desde = input('desde', date('Y-m-d', strtotime('-30 days')));
        $hasta = input('hasta', date('Y-m-d'));
        $suc = $this->sucursalFiltro();
        $tipo = input('tipo', '') ?: null;
        $rows = Reportes::movimientos($desde, $hasta, $suc, $tipo, input_int('producto_id'), 500);
        $this->view('inventario/movimientos', [
            'title' => 'Movimientos de inventario (kardex)', 'rows' => $rows, 'desde' => $desde, 'hasta' => $hasta,
            'suc' => $suc, 'tipo' => $tipo, 'sucursales' => Sucursal::delUsuario(),
        ]);
    }

    public function form(): void
    {
        $pid = input_int('producto_id');
        $this->view('inventario/movimiento_form', [
            'title' => 'Registrar movimiento', 'producto' => $pid ? Producto::find($pid) : null,
            'sucursales' => Sucursal::delUsuario(), 'tipo' => input('tipo', 'ENTRADA'),
        ]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $pid = (int)input_int('producto_id', 0);
        $sid = (int)input_int('sucursal_id', 0);
        $tipo = (string)input('tipo', '');
        $cant = (int)input_int('cantidad', -1);
        $motivo = (string)input('motivo', '');
        if (!$pid) throw new AppException('Seleccione un producto.');
        $nuevo = InventarioService::registrarManual($pid, $sid, $tipo, $cant, $motivo);
        $p = Producto::find($pid);
        $msg = "Movimiento registrado. Existencia actual de {$p['codigo']} en " . Sucursal::nombre($sid) . ": $nuevo.";
        if ($p['stock_minimo'] > 0 && $nuevo <= $p['stock_minimo']) {
            flash('warning', "⚠ {$p['nombre']} quedó en o por debajo del stock mínimo ({$p['stock_minimo']}).");
        }
        flash('success', $msg);
        if (input('otro')) redirect('movimientos/nuevo', ['tipo' => $tipo, 'sucursal_id' => $sid]);
        redirect('productos/ver', ['id' => $pid]);
    }

    /** Búsqueda de productos para autocompletar (JSON). */
    public function buscarJson(): void
    {
        if (!can('inventario.ver') && !can('pedidos.gestionar')) $this->json([]);
        $suc = input_int('sucursal_id');
        if ($suc && !Auth::puedeSucursal($suc)) $suc = Auth::sucursalFija();
        [$rows] = Producto::buscar(['q' => input('q', ''), 'activo' => '1', 'sucursal_id' => $suc], 15, 0);
        $out = [];
        foreach ($rows as $r) {
            $res = $suc ? InventarioService::reservado((int)$r['id'], $suc) : 0;
            $out[] = [
                'id' => (int)$r['id'], 'codigo' => $r['codigo'], 'nombre' => $r['nombre'],
                'vehiculo' => trim(($r['marca_vehiculo'] ?? '') . ' ' . ($r['modelo_vehiculo'] ?? '')),
                'precio' => (float)$r['precio'], 'stock' => (int)$r['stock'], 'disponible' => (int)$r['stock'] - $res,
            ];
        }
        $this->json($out);
    }
}
