<?php
final class ReporteController extends Controller
{
    public function index(): void
    {
        $this->view('reportes/index', ['title' => 'Reportes']);
    }

    /** Filtros comunes de período y sucursal. */
    private function periodo(): array
    {
        $desde = (string)input('desde', date('Y-m-01'));
        $hasta = (string)input('hasta', date('Y-m-d'));
        if (!strtotime($desde) || !strtotime($hasta)) throw new AppException('Fechas inválidas.');
        if ($desde > $hasta) [$desde, $hasta] = [$hasta, $desde];
        $suc = $this->sucursalFiltro();
        $txt = 'Del ' . fecha($desde, false) . ' al ' . fecha($hasta, false) . ' · ' . ($suc ? 'Sucursal ' . Sucursal::nombre($suc) : 'Todas las sucursales');
        return [$desde, $hasta, $suc, $txt];
    }

    private function formato(): ?string
    {
        $f = input('formato');
        return in_array($f, ['pdf', 'xlsx'], true) ? $f : null;
    }

    public function inventario(): void
    {
        $suc = $this->sucursalFiltro();
        $tipo = input_int('tipo_id');
        $bajo = (bool)input('bajo');
        $rows = Reportes::inventario($suc, $tipo, $bajo);
        $tot = ['unidades' => array_sum(array_column($rows, 'cantidad')),
                'costo' => array_sum(array_column($rows, 'valor_costo')),
                'venta' => array_sum(array_column($rows, 'valor_venta'))];
        if ($fmt = $this->formato()) {
            Exportador::enviar($fmt, 'Reporte de inventario' . ($bajo ? ' (stock bajo)' : ''),
                ($suc ? 'Sucursal ' . Sucursal::nombre($suc) : 'Todas las sucursales') . ' · Corte al ' . date('d/m/Y H:i'),
                [['Código', 'codigo', 'texto', 28], ['Producto', 'nombre', 'texto', 70], ['Tipo', 'tipo', 'texto', 35],
                 ['Sucursal', 'sucursal', 'texto', 35], ['Exist.', 'cantidad', 'entero', 16], ['Mín.', 'stock_minimo', 'entero', 14],
                 ['Costo', 'costo', 'dinero', 20], ['Precio', 'precio', 'dinero', 20], ['Valor costo', 'valor_costo', 'dinero', 24],
                 ['Valor venta', 'valor_venta', 'dinero', 24]],
                $rows, ['Unidades' => $tot['unidades'], 'Valor al costo' => $tot['costo'], 'Valor a precio de venta' => $tot['venta']]);
        }
        $this->view('reportes/inventario', ['title' => 'Reporte de inventario', 'rows' => $rows, 'tot' => $tot,
            'suc' => $suc, 'tipo' => $tipo, 'bajo' => $bajo, 'sucursales' => Sucursal::delUsuario(), 'tipos' => Producto::tipos()]);
    }

    public function ventas(): void
    {
        [$desde, $hasta, $suc, $txt] = $this->periodo();
        $agrupar = (string)input('agrupar', 'dia');
        $rows = Reportes::ventasAgrupadas($desde, $hasta, $agrupar, $suc);
        $tot = ['ventas' => array_sum(array_column($rows, 'ventas')), 'subtotal' => array_sum(array_column($rows, 'subtotal')),
                'descuento' => array_sum(array_column($rows, 'descuento')), 'total' => array_sum(array_column($rows, 'total'))];
        $etq = ['dia' => 'Día', 'mes' => 'Mes', 'sucursal' => 'Sucursal', 'vendedor' => 'Vendedor', 'metodo' => 'Método de pago'][$agrupar] ?? 'Grupo';
        if ($fmt = $this->formato()) {
            Exportador::enviar($fmt, "Reporte de ventas por " . mb_strtolower($etq), $txt,
                [[$etq, 'grupo', 'texto', 60], ['N° ventas', 'ventas', 'entero', 25], ['Subtotal', 'subtotal', 'dinero', 35],
                 ['Descuento', 'descuento', 'dinero', 30], ['Total', 'total', 'dinero', 35]],
                $rows, ['Ventas' => $tot['ventas'], 'Total vendido' => $tot['total']], 'P');
        }
        $this->view('reportes/ventas', ['title' => 'Reporte de ventas', 'rows' => $rows, 'tot' => $tot, 'desde' => $desde,
            'hasta' => $hasta, 'suc' => $suc, 'agrupar' => $agrupar, 'etq' => $etq, 'sucursales' => Sucursal::delUsuario()]);
    }

    public function productos(): void
    {
        [$desde, $hasta, $suc, $txt] = $this->periodo();
        $rows = Reportes::topProductos($desde, $hasta, $suc, 200);
        if ($fmt = $this->formato()) {
            Exportador::enviar($fmt, 'Ventas por producto', $txt,
                [['Código', 'codigo', 'texto', 35], ['Producto', 'nombre', 'texto', 90], ['Unidades', 'unidades', 'entero', 25], ['Monto', 'monto', 'dinero', 35]],
                $rows, ['Unidades' => array_sum(array_column($rows, 'unidades')), 'Monto' => array_sum(array_column($rows, 'monto'))], 'P');
        }
        $this->view('reportes/productos', ['title' => 'Ventas por producto', 'rows' => $rows, 'desde' => $desde,
            'hasta' => $hasta, 'suc' => $suc, 'sucursales' => Sucursal::delUsuario()]);
    }

    public function movimientos(): void
    {
        [$desde, $hasta, $suc, $txt] = $this->periodo();
        $tipo = input('tipo', '') ?: null;
        $rows = Reportes::movimientos($desde, $hasta, $suc, $tipo);
        foreach ($rows as &$r) $r['fecha_f'] = fecha($r['fecha']);
        unset($r);
        if ($fmt = $this->formato()) {
            Exportador::enviar($fmt, 'Movimientos de inventario' . ($tipo ? " ($tipo)" : ''), $txt,
                [['Fecha', 'fecha_f', 'texto', 28], ['Tipo', 'tipo', 'texto', 22], ['Código', 'codigo', 'texto', 25], ['Producto', 'nombre', 'texto', 55],
                 ['Sucursal', 'sucursal', 'texto', 30], ['Cant.', 'cantidad', 'entero', 14], ['Antes', 'stock_anterior', 'entero', 14],
                 ['Después', 'stock_resultante', 'entero', 16], ['Motivo', 'motivo', 'texto', 50], ['Usuario', 'usuario', 'texto', 26]],
                $rows);
        }
        $this->view('reportes/movimientos', ['title' => 'Reporte de movimientos', 'rows' => $rows, 'desde' => $desde,
            'hasta' => $hasta, 'suc' => $suc, 'tipo' => $tipo, 'sucursales' => Sucursal::delUsuario()]);
    }

    public function rotacion(): void
    {
        $desde = (string)input('desde', date('Y-m-d', strtotime('-90 days')));
        $_GET['desde'] = $desde;
        [$desde, $hasta, $suc, $txt] = $this->periodo();
        $rows = Reportes::rotacion($desde, $hasta, $suc);
        foreach ($rows as &$r) {
            $r['rot_txt'] = $r['rotacion'] === null ? 'Sin stock promedio' : num($r['rotacion'], 2);
            $r['dias_txt'] = $r['dias_inventario'] === null ? '—' : $r['dias_inventario'];
        }
        unset($r);
        if ($fmt = $this->formato()) {
            Exportador::enviar($fmt, 'Rotación de inventario', $txt . ' · Rotación = vendidas / existencia promedio',
                [['Código', 'codigo', 'texto', 28], ['Producto', 'nombre', 'texto', 75], ['Exist. inicial', 'stock_inicial', 'entero', 24],
                 ['Exist. final', 'stock_final', 'entero', 22], ['Promedio', 'stock_promedio', 'num', 22], ['Vendidas', 'vendidas', 'entero', 20],
                 ['Rotación', 'rot_txt', 'texto', 28], ['Días inv.', 'dias_txt', 'texto', 20]],
                $rows);
        }
        $this->view('reportes/rotacion', ['title' => 'Rotación de inventario', 'rows' => $rows, 'desde' => $desde,
            'hasta' => $hasta, 'suc' => $suc, 'sucursales' => Sucursal::delUsuario()]);
    }
}
