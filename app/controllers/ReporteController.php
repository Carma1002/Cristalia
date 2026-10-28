<?php
/**
 * Reportes e indicadores: 2 pantallas.
 *  - Ventas: por día, mes, sucursal, vendedor o método de pago + productos más vendidos.
 *  - Inventario: existencias, valor, estado de stock y rotación por producto.
 * Ambas se exportan a PDF y a hoja de cálculo (Excel).
 */
final class ReporteController extends Controller
{
    /** Período y sucursal comunes. */
    private function periodo(string $porDefectoDesde): array
    {
        $desde = (string)input('desde', $porDefectoDesde);
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

    public function ventas(): void
    {
        [$desde, $hasta, $suc, $txt] = $this->periodo(date('Y-m-01'));
        $agrupar = (string)input('agrupar', 'dia');
        if (!in_array($agrupar, ['dia', 'mes', 'sucursal', 'vendedor', 'metodo'], true)) $agrupar = 'dia';
        $etq = ['dia' => 'Día', 'mes' => 'Mes', 'sucursal' => 'Sucursal', 'vendedor' => 'Vendedor', 'metodo' => 'Método de pago'][$agrupar];
        $rows = Reportes::ventasAgrupadas($desde, $hasta, $agrupar, $suc);
        $top = Reportes::topProductos($desde, $hasta, $suc, 100);
        $tot = ['ventas' => array_sum(array_column($rows, 'ventas')), 'subtotal' => array_sum(array_column($rows, 'subtotal')),
                'descuento' => array_sum(array_column($rows, 'descuento')), 'total' => array_sum(array_column($rows, 'total'))];

        if ($fmt = $this->formato()) {
            if (input('tabla') === 'productos') {
                Exportador::enviar($fmt, 'Productos más vendidos', $txt,
                    [['Código', 'codigo', 'texto', 35], ['Producto', 'nombre', 'texto', 90], ['Unidades', 'unidades', 'entero', 25], ['Monto', 'monto', 'dinero', 35]],
                    $top, ['Unidades' => array_sum(array_column($top, 'unidades')), 'Monto' => array_sum(array_column($top, 'monto'))], 'P');
            }
            foreach ($rows as &$r) if ($agrupar === 'dia') $r['grupo'] = fecha($r['grupo'], false);
            unset($r);
            Exportador::enviar($fmt, 'Reporte de ventas por ' . mb_strtolower($etq), $txt,
                [[$etq, 'grupo', 'texto', 60], ['N° ventas', 'ventas', 'entero', 25], ['Subtotal', 'subtotal', 'dinero', 35],
                 ['Descuento', 'descuento', 'dinero', 30], ['Total', 'total', 'dinero', 35]],
                $rows, ['Ventas' => $tot['ventas'], 'Total vendido' => $tot['total']], 'P');
        }
        $this->view('reportes/ventas', ['title' => 'Reporte de ventas', 'rows' => $rows, 'top' => $top, 'tot' => $tot, 'desde' => $desde,
            'hasta' => $hasta, 'suc' => $suc, 'agrupar' => $agrupar, 'etq' => $etq, 'sucursales' => Sucursal::delUsuario()]);
    }

    public function inventario(): void
    {
        [$desde, $hasta, $suc, $txt] = $this->periodo(date('Y-m-d', strtotime('-90 days')));
        $tipo = input_int('tipo_id');
        $reponer = (bool)input('reponer');
        $rows = Reportes::inventarioDetallado($suc, $tipo, $reponer, $desde, $hasta);
        $orden = (string)input('orden', '');
        $cmp = match ($orden) {
            'rotacion' => fn($a, $b) => [(float)($b['rotacion'] ?? 0), $b['vendidas']] <=> [(float)($a['rotacion'] ?? 0), $a['vendidas']],
            'valor' => fn($a, $b) => $b['valor_costo'] <=> $a['valor_costo'],
            'existencia' => fn($a, $b) => $a['existencia'] <=> $b['existencia'],
            default => null,
        };
        if ($cmp) usort($rows, $cmp);
        foreach ($rows as &$r) {
            $r['rot_txt'] = $r['rotacion'] === null ? 'Sin stock' : num($r['rotacion'], 2);
            $r['dias_txt'] = $r['dias_inventario'] === null ? '—' : (string)$r['dias_inventario'];
        }
        unset($r);
        $tot = ['unidades' => array_sum(array_column($rows, 'existencia')), 'costo' => array_sum(array_column($rows, 'valor_costo')),
                'venta' => array_sum(array_column($rows, 'valor_venta')),
                'reponer' => count(array_filter($rows, fn($r) => $r['estado'] !== 'Disponible')), 'vendidas' => array_sum(array_column($rows, 'vendidas'))];

        if ($fmt = $this->formato()) {
            Exportador::enviar($fmt, 'Reporte de inventario' . ($reponer ? ' · productos por reponer' : ''),
                ($suc ? 'Sucursal ' . Sucursal::nombre($suc) : 'Todas las sucursales') . ' · Existencias al ' . date('d/m/Y H:i') . ' · Rotación del ' . fecha($desde, false) . ' al ' . fecha($hasta, false),
                [['Código', 'codigo', 'texto', 26], ['Producto', 'nombre', 'texto', 62], ['Tipo', 'tipo', 'texto', 30], ['Exist.', 'existencia', 'entero', 14],
                 ['Mín.', 'stock_minimo', 'entero', 12], ['Estado', 'estado', 'texto', 22], ['Valor costo', 'valor_costo', 'dinero', 24],
                 ['Valor venta', 'valor_venta', 'dinero', 24], ['Vendidas', 'vendidas', 'entero', 16], ['Rotación', 'rot_txt', 'texto', 18], ['Días inv.', 'dias_txt', 'texto', 16]],
                $rows, ['Unidades' => $tot['unidades'], 'Valor al costo' => $tot['costo'], 'Valor a precio de venta' => $tot['venta'], 'Productos por reponer' => $tot['reponer']]);
        }
        $this->view('reportes/inventario', ['title' => 'Reporte de inventario', 'rows' => $rows, 'tot' => $tot, 'desde' => $desde, 'hasta' => $hasta,
            'suc' => $suc, 'tipo' => $tipo, 'reponer' => $reponer, 'orden' => $orden, 'sucursales' => Sucursal::delUsuario(), 'tipos' => Producto::tipos()]);
    }
}
