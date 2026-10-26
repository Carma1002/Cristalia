<?php
/**
 * Acceso a datos de ventas.
 */
final class Venta
{
    const METODOS_PAGO = ['Efectivo', 'Transferencia', 'Pago móvil', 'Punto de venta', 'Divisas', 'Zelle', 'Mixto'];

    public static function find(int $id): ?array
    {
        return DB::one("SELECT v.*, c.nombre AS cliente, CONCAT(c.tipo_documento,'-',c.documento) AS cliente_doc,
                               c.telefono AS cliente_tel, c.direccion AS cliente_dir, c.email AS cliente_email,
                               s.nombre AS sucursal, s.direccion AS sucursal_dir, s.telefono AS sucursal_tel,
                               u.nombre AS usuario, p.numero AS pedido_numero
                        FROM ventas v JOIN clientes c ON c.id = v.cliente_id
                        JOIN sucursales s ON s.id = v.sucursal_id
                        JOIN pedidos p ON p.id = v.pedido_id
                        LEFT JOIN usuarios u ON u.id = v.usuario_id WHERE v.id = ?", [$id]);
    }

    public static function detalle(int $id): array
    {
        return DB::all('SELECT d.*, pr.codigo, pr.nombre FROM venta_detalle d
                        JOIN productos pr ON pr.id = d.producto_id WHERE d.venta_id = ? ORDER BY d.id', [$id]);
    }

    public static function buscar(array $f, int $limit = 20, int $offset = 0): array
    {
        $w = ['1=1'];
        $p = [];
        if (!empty($f['q'])) {
            $w[] = '(v.numero LIKE :q1 OR c.nombre LIKE :q2 OR c.documento LIKE :q3)';
            $p['q1'] = $p['q2'] = $p['q3'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['estado']))      { $w[] = 'v.estado = :est'; $p['est'] = $f['estado']; }
        if (!empty($f['sucursal_id'])) { $w[] = 'v.sucursal_id = :suc'; $p['suc'] = (int)$f['sucursal_id']; }
        if (!empty($f['cliente_id']))  { $w[] = 'v.cliente_id = :cli'; $p['cli'] = (int)$f['cliente_id']; }
        if (!empty($f['desde']))       { $w[] = 'v.fecha >= :d'; $p['d'] = $f['desde'] . ' 00:00:00'; }
        if (!empty($f['hasta']))       { $w[] = 'v.fecha <= :h'; $p['h'] = $f['hasta'] . ' 23:59:59'; }
        $ws = implode(' AND ', $w);
        $tot = DB::one("SELECT COUNT(*) AS n, COALESCE(SUM(CASE WHEN v.estado='ACTIVA' THEN v.total ELSE 0 END),0) AS monto
                        FROM ventas v JOIN clientes c ON c.id = v.cliente_id WHERE $ws", $p);
        $rows = DB::all("SELECT v.*, c.nombre AS cliente, s.nombre AS sucursal
                         FROM ventas v JOIN clientes c ON c.id = v.cliente_id JOIN sucursales s ON s.id = v.sucursal_id
                         WHERE $ws ORDER BY v.id DESC LIMIT :lim OFFSET :off",
            array_merge($p, ['lim' => $limit, 'off' => $offset]));
        return [$rows, (int)$tot['n'], (float)$tot['monto']];
    }
}
