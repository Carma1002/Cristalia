<?php
/**
 * Acceso a datos de pedidos.
 */
final class Pedido
{
    const ESTADOS = ['PENDIENTE', 'CONFIRMADO', 'COMPLETADO', 'CANCELADO'];

    public static function find(int $id): ?array
    {
        return DB::one("SELECT p.*, c.nombre AS cliente, CONCAT(c.tipo_documento,'-',c.documento) AS cliente_doc,
                               c.telefono AS cliente_tel, s.nombre AS sucursal, u.nombre AS usuario,
                               v.id AS venta_id, v.numero AS venta_numero
                        FROM pedidos p JOIN clientes c ON c.id = p.cliente_id
                        JOIN sucursales s ON s.id = p.sucursal_id
                        LEFT JOIN usuarios u ON u.id = p.usuario_id
                        LEFT JOIN ventas v ON v.pedido_id = p.id
                        WHERE p.id = ?", [$id]);
    }

    public static function detalle(int $id): array
    {
        return DB::all('SELECT d.*, pr.codigo, pr.nombre FROM pedido_detalle d
                        JOIN productos pr ON pr.id = d.producto_id WHERE d.pedido_id = ? ORDER BY d.id', [$id]);
    }

    public static function historial(int $id): array
    {
        return DB::all('SELECT h.*, u.nombre AS usuario FROM pedido_historial h
                        LEFT JOIN usuarios u ON u.id = h.usuario_id WHERE h.pedido_id = ? ORDER BY h.id', [$id]);
    }

    public static function buscar(array $f, int $limit = 20, int $offset = 0): array
    {
        $w = ['1=1'];
        $p = [];
        if (!empty($f['q'])) {
            $w[] = '(p.numero LIKE :q1 OR c.nombre LIKE :q2 OR c.documento LIKE :q3)';
            $p['q1'] = $p['q2'] = $p['q3'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['estado']))      { $w[] = 'p.estado = :est'; $p['est'] = $f['estado']; }
        if (!empty($f['sucursal_id'])) { $w[] = 'p.sucursal_id = :suc'; $p['suc'] = (int)$f['sucursal_id']; }
        if (!empty($f['cliente_id']))  { $w[] = 'p.cliente_id = :cli'; $p['cli'] = (int)$f['cliente_id']; }
        if (!empty($f['desde']))       { $w[] = 'p.fecha >= :d'; $p['d'] = $f['desde'] . ' 00:00:00'; }
        if (!empty($f['hasta']))       { $w[] = 'p.fecha <= :h'; $p['h'] = $f['hasta'] . ' 23:59:59'; }
        $ws = implode(' AND ', $w);
        $total = (int)DB::value("SELECT COUNT(*) FROM pedidos p JOIN clientes c ON c.id = p.cliente_id WHERE $ws", $p);
        $rows = DB::all("SELECT p.*, c.nombre AS cliente, s.nombre AS sucursal,
                           (SELECT COUNT(*) FROM pedido_detalle d WHERE d.pedido_id = p.id) AS items
                         FROM pedidos p JOIN clientes c ON c.id = p.cliente_id JOIN sucursales s ON s.id = p.sucursal_id
                         WHERE $ws ORDER BY p.id DESC LIMIT :lim OFFSET :off",
            array_merge($p, ['lim' => $limit, 'off' => $offset]));
        return [$rows, $total];
    }

    public static function contarAbiertos(?int $sucursalId = null): int
    {
        $sql = "SELECT COUNT(*) FROM pedidos WHERE estado IN ('PENDIENTE','CONFIRMADO')";
        return (int)($sucursalId ? DB::value("$sql AND sucursal_id = ?", [$sucursalId]) : DB::value($sql));
    }
}
