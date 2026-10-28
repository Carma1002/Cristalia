<?php
final class InventarioController extends Controller
{
    /** Entradas, salidas y ajustes (kardex), con exportación a PDF / Excel. */
    public function movimientos(): void
    {
        $desde = (string)input('desde', date('Y-m-d', strtotime('-30 days')));
        $hasta = (string)input('hasta', date('Y-m-d'));
        if (!strtotime($desde) || !strtotime($hasta)) throw new AppException('Fechas inválidas.');
        if ($desde > $hasta) [$desde, $hasta] = [$hasta, $desde];
        $suc = $this->sucursalFiltro();
        $tipo = input('tipo', '') ?: null;
        $pid = input_int('producto_id');
        $producto = $pid ? Producto::find($pid) : null;

        if (in_array(input('formato'), ['pdf', 'xlsx'], true)) {
            $rows = Reportes::movimientos($desde, $hasta, $suc, $tipo, $pid);
            foreach ($rows as &$r) $r['fecha_f'] = fecha($r['fecha']);
            unset($r);
            Exportador::enviar((string)input('formato'), 'Entradas y salidas de inventario' . ($producto ? ' · ' . $producto['codigo'] : '') . ($tipo ? " ($tipo)" : ''),
                'Del ' . fecha($desde, false) . ' al ' . fecha($hasta, false) . ' · ' . ($suc ? 'Sucursal ' . Sucursal::nombre($suc) : 'Todas las sucursales'),
                [['Fecha', 'fecha_f', 'texto', 28], ['Tipo', 'tipo', 'texto', 22], ['Código', 'codigo', 'texto', 25], ['Producto', 'nombre', 'texto', 55],
                 ['Sucursal', 'sucursal', 'texto', 30], ['Cant.', 'cantidad', 'entero', 14], ['Antes', 'stock_anterior', 'entero', 14],
                 ['Después', 'stock_resultante', 'entero', 16], ['Motivo', 'motivo', 'texto', 50], ['Usuario', 'usuario', 'texto', 26]],
                $rows);
        }

        $rows = Reportes::movimientos($desde, $hasta, $suc, $tipo, $pid, 300);
        $resumen = [];
        foreach ($rows as $r) $resumen[$r['tipo']] = ($resumen[$r['tipo']] ?? 0) + (int)$r['cantidad'];
        $this->view('inventario/movimientos', [
            'title' => 'Entradas y salidas', 'rows' => $rows, 'desde' => $desde, 'hasta' => $hasta, 'resumen' => $resumen,
            'suc' => $suc, 'tipo' => $tipo, 'producto' => $producto, 'sucursales' => Sucursal::delUsuario(),
        ]);
    }

    public function form(): void
    {
        $pid = input_int('producto_id') ?? (int)old('producto_id', 0);
        $this->view('inventario/movimiento_form', [
            'title' => 'Registrar entrada o salida', 'producto' => $pid ? Producto::find($pid) : null,
            'sucursales' => Sucursal::delUsuario(), 'tipo' => old('tipo', input('tipo', 'ENTRADA')),
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
        if (!$pid) throw new AppException('Busque y seleccione el producto.');
        $nuevo = InventarioService::registrarManual($pid, $sid, $tipo, $cant, $motivo);
        $p = Producto::find($pid);
        if ($p['stock_minimo'] > 0 && $nuevo <= $p['stock_minimo']) {
            flash('warning', "{$p['nombre']} quedó en o por debajo del stock mínimo ({$p['stock_minimo']}).");
        }
        $verbo = ['ENTRADA' => 'Entrada registrada', 'SALIDA' => 'Salida registrada', 'AJUSTE' => 'Ajuste registrado'][$tipo] ?? 'Movimiento registrado';
        flash('success', "$verbo. Ahora hay $nuevo unidades de {$p['codigo']} en " . Sucursal::nombre($sid) . '.');
        if (input('otro')) redirect('movimientos/nuevo', ['tipo' => $tipo, 'sucursal_id' => $sid]);
        redirect('productos/ver', ['id' => $pid]);
    }

    /** Búsqueda de productos para autocompletar y búsqueda rápida (JSON). */
    public function buscarJson(): void
    {
        if (!can(['inventario.ver', 'catalogo.ver', 'pedidos.gestionar'])) $this->json([]);
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
                'imagen' => $r['imagen'],
            ];
        }
        $this->json($out);
    }
}
