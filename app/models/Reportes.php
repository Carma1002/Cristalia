<?php
/**
 * Consultas de reportes e indicadores.
 */
final class Reportes
{
    private static function suc(?int $suc, string $col = 'sucursal_id'): string
    {
        return $suc ? " AND $col = " . (int)$suc : '';
    }

    /** Ventas de los últimos N meses (rellena meses sin ventas con 0). */
    public static function ventasMensuales(int $meses = 12, ?int $suc = null): array
    {
        $desde = date('Y-m-01', strtotime('-' . ($meses - 1) . ' months'));
        $rows = DB::all("SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, COUNT(*) AS n, SUM(total) AS monto
                         FROM ventas WHERE estado = 'ACTIVA' AND fecha >= ?" . self::suc($suc) . "
                         GROUP BY DATE_FORMAT(fecha, '%Y-%m') ORDER BY mes", ["$desde 00:00:00"]);
        $idx = array_column($rows, null, 'mes');
        $nombres = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $out = [];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $m = date('Y-m', strtotime(date('Y-m-01') . " -$i months"));
            $out[] = [
                'mes' => $m,
                'etiqueta' => $nombres[(int)substr($m, 5, 2)] . ' ' . substr($m, 2, 2),
                'n' => (int)($idx[$m]['n'] ?? 0),
                'monto' => round((float)($idx[$m]['monto'] ?? 0), 2),
            ];
        }
        return $out;
    }

    public static function topProductos(string $desde, string $hasta, ?int $suc = null, int $limit = 10): array
    {
        return DB::all("SELECT p.id, p.codigo, p.nombre, SUM(d.cantidad) AS unidades, SUM(d.subtotal) AS monto
                        FROM venta_detalle d JOIN ventas v ON v.id = d.venta_id JOIN productos p ON p.id = d.producto_id
                        WHERE v.estado = 'ACTIVA' AND v.fecha BETWEEN ? AND ?" . self::suc($suc, 'v.sucursal_id') . "
                        GROUP BY p.id, p.codigo, p.nombre ORDER BY unidades DESC, monto DESC LIMIT " . (int)$limit,
            ["$desde 00:00:00", "$hasta 23:59:59"]);
    }

    /**
     * Rotación de inventario por producto en un período:
     *   rotación = unidades vendidas / existencia promedio
     *   existencia promedio = (existencia inicial + existencia final) / 2
     *   existencia inicial se reconstruye desde el kardex (movimientos).
     */
    public static function rotacion(string $desde, string $hasta, ?int $suc = null): array
    {
        $ini = "$desde 00:00:00";
        $fin = "$hasta 23:59:59";
        $sucE = self::suc($suc, 'e.sucursal_id');
        $sucM = self::suc($suc, 'm.sucursal_id');
        $sucV = self::suc($suc, 'v.sucursal_id');
        $rows = DB::all("SELECT p.id, p.codigo, p.nombre,
                  (SELECT COALESCE(SUM(e.cantidad),0) FROM existencias e WHERE e.producto_id = p.id $sucE) AS stock_actual,
                  (SELECT COALESCE(SUM(m.cantidad),0) FROM movimientos m WHERE m.producto_id = p.id AND m.fecha > :fin1 $sucM) AS mov_post,
                  (SELECT COALESCE(SUM(m.cantidad),0) FROM movimientos m WHERE m.producto_id = p.id AND m.fecha BETWEEN :ini1 AND :fin2 $sucM) AS mov_periodo,
                  (SELECT COALESCE(SUM(d.cantidad),0) FROM venta_detalle d JOIN ventas v ON v.id = d.venta_id
                     WHERE d.producto_id = p.id AND v.estado = 'ACTIVA' AND v.fecha BETWEEN :ini2 AND :fin3 $sucV) AS vendidas
                FROM productos p WHERE p.activo = 1",
            ['fin1' => $fin, 'ini1' => $ini, 'fin2' => $fin, 'ini2' => $ini, 'fin3' => $fin]);
        $dias = max(1, (int)round((strtotime($fin) - strtotime($ini)) / 86400));
        foreach ($rows as &$r) {
            $final = (int)$r['stock_actual'] - (int)$r['mov_post'];
            $inicial = $final - (int)$r['mov_periodo'];
            $prom = ($inicial + $final) / 2;
            $r['stock_inicial'] = $inicial;
            $r['stock_final'] = $final;
            $r['stock_promedio'] = round($prom, 1);
            $r['rotacion'] = $prom > 0 ? round($r['vendidas'] / $prom, 2) : ($r['vendidas'] > 0 ? null : 0);
            $r['dias_inventario'] = ($r['rotacion'] ?? 0) > 0 ? (int)round($dias / $r['rotacion']) : null;
        }
        unset($r);
        usort($rows, fn($a, $b) => [(float)($b['rotacion'] ?? 999), $b['vendidas']] <=> [(float)($a['rotacion'] ?? 999), $a['vendidas']]);
        return $rows;
    }

    /**
     * Reporte de inventario unificado por producto: existencia, estado, valor y rotación del período.
     * Reúne en una sola tabla el inventario valorizado, la rotación y los productos por reponer.
     */
    public static function inventarioDetallado(?int $suc, ?int $tipoId, bool $soloReponer, string $desde, string $hasta): array
    {
        $rot = array_column(self::rotacion($desde, $hasta, $suc), null, 'id');
        $bajos = array_flip(array_map('intval', array_column(Producto::stockBajo($suc, 100000), 'id')));
        $sql = 'SELECT p.id, p.codigo, p.nombre, p.stock_minimo, p.costo, p.precio, t.nombre AS tipo
                FROM productos p JOIN tipos_producto t ON t.id = p.tipo_id WHERE p.activo = 1';
        $par = [];
        if ($tipoId) { $sql .= ' AND p.tipo_id = ?'; $par[] = $tipoId; }
        $out = [];
        foreach (DB::all("$sql ORDER BY p.nombre", $par) as $p) {
            $r = $rot[$p['id']] ?? ['stock_actual' => 0, 'vendidas' => 0, 'rotacion' => 0, 'dias_inventario' => null, 'stock_promedio' => 0];
            $ex = (int)$r['stock_actual'];
            $estado = $ex <= 0 ? 'Agotado' : (isset($bajos[(int)$p['id']]) ? 'Por reponer' : 'Disponible');
            if ($soloReponer && $estado === 'Disponible') continue;
            $out[] = $p + [
                'existencia' => $ex, 'estado' => $estado,
                'valor_costo' => round($ex * (float)$p['costo'], 2), 'valor_venta' => round($ex * (float)$p['precio'], 2),
                'vendidas' => (int)$r['vendidas'], 'stock_promedio' => $r['stock_promedio'],
                'rotacion' => $r['rotacion'], 'dias_inventario' => $r['dias_inventario'],
            ];
        }
        return $out;
    }

    /** Inventario valorizado por producto (y sucursal opcional). */
    public static function inventario(?int $suc, ?int $tipoId = null, bool $soloBajo = false): array
    {
        $p = [];
        $sql = "SELECT p.codigo, p.nombre, t.nombre AS tipo, s.nombre AS sucursal, COALESCE(e.cantidad,0) AS cantidad,
                       p.stock_minimo, p.costo, p.precio,
                       COALESCE(e.cantidad,0) * p.costo AS valor_costo, COALESCE(e.cantidad,0) * p.precio AS valor_venta
                FROM productos p JOIN tipos_producto t ON t.id = p.tipo_id
                CROSS JOIN sucursales s
                LEFT JOIN existencias e ON e.producto_id = p.id AND e.sucursal_id = s.id
                WHERE p.activo = 1 AND s.activo = 1";
        if ($suc)    { $sql .= ' AND s.id = ?'; $p[] = $suc; }
        if ($tipoId) { $sql .= ' AND p.tipo_id = ?'; $p[] = $tipoId; }
        if ($soloBajo) $sql .= ' AND p.stock_minimo > 0 AND COALESCE(e.cantidad,0) <= p.stock_minimo';
        return DB::all("$sql ORDER BY s.nombre, p.nombre", $p);
    }

    /** Ventas agrupadas: por día, mes, sucursal, vendedor o método de pago. */
    public static function ventasAgrupadas(string $desde, string $hasta, string $agrupar, ?int $suc): array
    {
        [$expr, $join] = match ($agrupar) {
            'mes'      => ["DATE_FORMAT(v.fecha, '%Y-%m')", ''],
            'sucursal' => ['s.nombre', 'JOIN sucursales s ON s.id = v.sucursal_id'],
            'vendedor' => ["COALESCE(u.nombre,'(sin usuario)')", 'LEFT JOIN usuarios u ON u.id = v.usuario_id'],
            'metodo'   => ['v.metodo_pago', ''],
            default    => ['DATE(v.fecha)', ''],
        };
        return DB::all("SELECT $expr AS grupo, COUNT(*) AS ventas, SUM(v.subtotal) AS subtotal,
                               SUM(v.descuento) AS descuento, SUM(v.total) AS total
                        FROM ventas v $join
                        WHERE v.estado = 'ACTIVA' AND v.fecha BETWEEN ? AND ?" . self::suc($suc, 'v.sucursal_id') . "
                        GROUP BY $expr ORDER BY grupo", ["$desde 00:00:00", "$hasta 23:59:59"]);
    }

    public static function movimientos(string $desde, string $hasta, ?int $suc, ?string $tipo, ?int $productoId = null, int $limit = 5000): array
    {
        $sql = "SELECT m.fecha, m.tipo, p.codigo, p.nombre, s.nombre AS sucursal, m.cantidad, m.stock_anterior,
                       m.stock_resultante, m.motivo, m.referencia, u.nombre AS usuario
                FROM movimientos m JOIN productos p ON p.id = m.producto_id JOIN sucursales s ON s.id = m.sucursal_id
                LEFT JOIN usuarios u ON u.id = m.usuario_id
                WHERE m.fecha BETWEEN ? AND ?";
        $p = ["$desde 00:00:00", "$hasta 23:59:59"];
        if ($suc)        { $sql .= ' AND m.sucursal_id = ?'; $p[] = $suc; }
        if ($tipo)       { $sql .= ' AND m.tipo = ?'; $p[] = $tipo; }
        if ($productoId) { $sql .= ' AND m.producto_id = ?'; $p[] = $productoId; }
        return DB::all("$sql ORDER BY m.id DESC LIMIT " . (int)$limit, $p);
    }
}
