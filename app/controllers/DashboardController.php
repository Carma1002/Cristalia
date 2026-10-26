<?php
final class DashboardController extends Controller
{
    public function index(): void
    {
        if (!can('dashboard.ver')) {
            $this->view('dashboard/simple', ['title' => 'Inicio']);
            return;
        }
        $suc = $this->sucursalFiltro();
        $sw = $suc ? ' AND sucursal_id = ' . (int)$suc : '';

        $hoy = date('Y-m-d');
        $iniMes = date('Y-m-01');
        $kpi = [
            'ventas_hoy'   => DB::one("SELECT COUNT(*) n, COALESCE(SUM(total),0) m FROM ventas WHERE estado='ACTIVA' AND fecha >= ? $sw", ["$hoy 00:00:00"]),
            'ventas_mes'   => DB::one("SELECT COUNT(*) n, COALESCE(SUM(total),0) m FROM ventas WHERE estado='ACTIVA' AND fecha >= ? $sw", ["$iniMes 00:00:00"]),
            'pedidos_abiertos' => Pedido::contarAbiertos($suc),
            'stock_bajo'   => InventarioService::contarStockBajo($suc),
            'productos'    => (int)DB::value('SELECT COUNT(*) FROM productos WHERE activo = 1'),
            'valor_inv'    => (float)DB::value('SELECT COALESCE(SUM(e.cantidad * p.costo),0) FROM existencias e JOIN productos p ON p.id = e.producto_id WHERE 1=1' . ($suc ? ' AND e.sucursal_id = ' . (int)$suc : '')),
        ];

        $this->view('dashboard/index', [
            'title'      => 'Panel de indicadores',
            'kpi'        => $kpi,
            'mensual'    => Reportes::ventasMensuales(12, $suc),
            'top'        => Reportes::topProductos(date('Y-m-d', strtotime('-90 days')), $hoy, $suc, 5),
            'stockBajo'  => Producto::stockBajo($suc, 8),
            'rotacion'   => array_slice(Reportes::rotacion(date('Y-m-d', strtotime('-90 days')), $hoy, $suc), 0, 5),
            'ultimos'    => Pedido::buscar(['sucursal_id' => $suc], 6)[0],
            'sucursales' => Sucursal::delUsuario(),
            'suc'        => $suc,
        ]);
    }
}
