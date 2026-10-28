<?php
final class DashboardController extends Controller
{
    public function index(): void
    {
        $suc = $this->sucursalFiltro();
        $sw = $suc ? ' AND sucursal_id = ' . (int)$suc : '';
        $hoy = date('Y-m-d');
        $iniMes = date('Y-m-01');
        $dia = (int)date('j');
        $iniAnt = date('Y-m-01', strtotime('first day of last month'));
        $finAnt = date('Y-m-d', min(strtotime($iniAnt . ' +' . ($dia - 1) . ' days'), strtotime('last day of last month')));

        $ventasHoy = DB::one("SELECT COUNT(*) n, COALESCE(SUM(total),0) m FROM ventas WHERE estado='ACTIVA' AND fecha >= ? $sw", ["$hoy 00:00:00"]);
        $ventasMes = DB::one("SELECT COUNT(*) n, COALESCE(SUM(total),0) m FROM ventas WHERE estado='ACTIVA' AND fecha >= ? $sw", ["$iniMes 00:00:00"]);
        $ventasAnt = (float)DB::value("SELECT COALESCE(SUM(total),0) FROM ventas WHERE estado='ACTIVA' AND fecha BETWEEN ? AND ? $sw", ["$iniAnt 00:00:00", "$finAnt 23:59:59"]);

        // Últimos 7 días (para la mini gráfica de barras)
        $dias = [];
        foreach (DB::all("SELECT DATE(fecha) d, SUM(total) m FROM ventas WHERE estado='ACTIVA' AND fecha >= ? $sw GROUP BY DATE(fecha)",
                     [date('Y-m-d', strtotime('-6 days')) . ' 00:00:00']) as $r) $dias[$r['d']] = (float)$r['m'];
        $semana = [];
        for ($i = 6; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i days")); $semana[] = $dias[$d] ?? 0.0; }

        $abiertos = ['PENDIENTE' => 0, 'CONFIRMADO' => 0];
        foreach (DB::all("SELECT estado, COUNT(*) n FROM pedidos WHERE estado IN ('PENDIENTE','CONFIRMADO') $sw GROUP BY estado") as $r) $abiertos[$r['estado']] = (int)$r['n'];

        $bajo = Producto::stockBajo($suc, 500);
        $agotados = count(array_filter($bajo, fn($b) => (int)$b['cantidad'] === 0));

        $porSucursal = DB::all("SELECT s.nombre, COALESCE(SUM(v.total),0) monto FROM sucursales s
                                LEFT JOIN ventas v ON v.sucursal_id = s.id AND v.estado = 'ACTIVA' AND v.fecha >= ?
                                WHERE s.activo = 1" . ($suc ? ' AND s.id = ' . (int)$suc : '') . " GROUP BY s.id, s.nombre ORDER BY monto DESC",
            ["$iniMes 00:00:00"]);

        $this->view('dashboard/index', [
            'title'      => 'Inicio',
            'suc'        => $suc,
            'sucursales' => Sucursal::delUsuario(),
            'ventasHoy'  => $ventasHoy,
            'ventasMes'  => $ventasMes,
            'ventasAnt'  => $ventasAnt,
            'semana'     => $semana,
            'abiertos'   => $abiertos,
            'bajo'       => array_slice($bajo, 0, 6),
            'nBajo'      => count($bajo),
            'agotados'   => $agotados,
            'mensual'    => Reportes::ventasMensuales(12, $suc),
            'porSucursal'=> $porSucursal,
            'top'        => Reportes::topProductos(date('Y-m-d', strtotime('-90 days')), $hoy, $suc, 5),
            'ultimos'    => can('pedidos.ver') ? Pedido::buscar(['sucursal_id' => $suc], 5)[0] : [],
        ]);
    }
}
