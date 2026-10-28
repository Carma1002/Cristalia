<?php
/**
 * Acceso a datos de productos.
 */
final class Producto
{
    public static function find(int $id): ?array
    {
        return DB::one('SELECT p.*, t.nombre AS tipo_nombre FROM productos p
                        JOIN tipos_producto t ON t.id = p.tipo_id WHERE p.id = ?', [$id]);
    }

    public static function porCodigo(string $codigo): ?array
    {
        return DB::one('SELECT * FROM productos WHERE codigo = ?', [$codigo]);
    }

    /**
     * Búsqueda con filtros. Devuelve [filas, total].
     * Filtros: q (código/nombre/descr.), tipo_id, marca, modelo, anio, activo, sucursal_id (para existencia)
     */
    public static function buscar(array $f, int $limit = 20, int $offset = 0): array
    {
        $where = ['1=1'];
        $p = [];
        if (!empty($f['q'])) {
            $where[] = "(p.codigo LIKE :q1 OR p.nombre LIKE :q2 OR p.descripcion LIKE :q3
                         OR CONCAT(COALESCE(p.marca_vehiculo,''),' ',COALESCE(p.modelo_vehiculo,'')) LIKE :q4)";
            $p['q1'] = $p['q2'] = $p['q3'] = $p['q4'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['tipo_id']))  { $where[] = 'p.tipo_id = :tipo'; $p['tipo'] = (int)$f['tipo_id']; }
        if (!empty($f['marca']))    { $where[] = 'p.marca_vehiculo = :marca'; $p['marca'] = $f['marca']; }
        if (!empty($f['modelo']))   { $where[] = 'p.modelo_vehiculo LIKE :modelo'; $p['modelo'] = '%' . $f['modelo'] . '%'; }
        if (!empty($f['anio'])) {
            $where[] = '(p.anio_desde IS NULL OR p.anio_desde <= :a1) AND (p.anio_hasta IS NULL OR p.anio_hasta >= :a2)';
            $p['a1'] = $p['a2'] = (int)$f['anio'];
        }
        if (isset($f['activo']) && $f['activo'] !== '') { $where[] = 'p.activo = :act'; $p['act'] = (int)$f['activo']; }

        $suc = !empty($f['sucursal_id']) ? (int)$f['sucursal_id'] : 0;
        $stockExpr = function (string $alias) use ($suc, &$p): string {
            if (!$suc) {
                return "(SELECT COALESCE(SUM($alias.cantidad),0) FROM existencias $alias JOIN sucursales s$alias
                         ON s$alias.id = $alias.sucursal_id AND s$alias.activo = 1 WHERE $alias.producto_id = p.id)";
            }
            $p["suc_$alias"] = $suc;
            return "(SELECT COALESCE(SUM($alias.cantidad),0) FROM existencias $alias
                     WHERE $alias.producto_id = p.id AND $alias.sucursal_id = :suc_$alias)";
        };
        if (!empty($f['solo_disponibles'])) $where[] = $stockExpr('ex2') . ' > 0';
        // Estado de stock: disponible | bajo (en o bajo el mínimo en alguna sucursal) | agotado
        switch ($f['estado'] ?? '') {
            case 'disponible': $where[] = $stockExpr('ex3') . ' > 0'; break;
            case 'agotado':    $where[] = $stockExpr('ex3') . ' <= 0'; break;
            case 'bajo':
                $where[] = "p.stock_minimo > 0 AND EXISTS (SELECT 1 FROM sucursales sb LEFT JOIN existencias eb
                              ON eb.producto_id = p.id AND eb.sucursal_id = sb.id
                            WHERE sb.activo = 1" . ($suc ? ' AND sb.id = :suc_b' : '') . " AND COALESCE(eb.cantidad,0) <= p.stock_minimo)";
                if ($suc) $p['suc_b'] = $suc;
                break;
        }
        $w = implode(' AND ', $where);
        $pCount = $p;
        $stockSel = $stockExpr('ex1');

        $total = (int)DB::value("SELECT COUNT(*) FROM productos p WHERE $w", $pCount);
        $orden = match ($f['orden'] ?? '') {
            'precio_asc' => 'p.precio ASC', 'precio_desc' => 'p.precio DESC',
            'codigo' => 'p.codigo ASC', 'stock' => 'stock ASC', default => 'p.nombre ASC',
        };
        $rows = DB::all("SELECT p.*, t.nombre AS tipo_nombre, $stockSel AS stock
                         FROM productos p JOIN tipos_producto t ON t.id = p.tipo_id
                         WHERE $w ORDER BY $orden LIMIT :lim OFFSET :off",
            array_merge($p, ['lim' => $limit, 'off' => $offset]));
        return [$rows, $total];
    }

    /** Cantidad de productos por estado de stock con los mismos filtros (para los contadores). */
    public static function contarEstados(array $f): array
    {
        $out = [];
        foreach (['' => 'todos', 'disponible' => 'disponible', 'bajo' => 'bajo', 'agotado' => 'agotado'] as $e => $k) {
            $out[$k] = self::buscar(array_merge($f, ['estado' => $e]), 1, 0)[1];
        }
        return $out;
    }

    /** Existencias de un producto en cada sucursal activa (incluye reservado y disponible). */
    public static function existenciasPorSucursal(int $productoId): array
    {
        return DB::all(
            "SELECT s.id AS sucursal_id, s.nombre AS sucursal, COALESCE(e.cantidad,0) AS cantidad,
                    (SELECT COALESCE(SUM(d.cantidad),0) FROM pedido_detalle d JOIN pedidos pe ON pe.id = d.pedido_id
                      WHERE d.producto_id = :p1 AND pe.sucursal_id = s.id AND pe.estado IN ('PENDIENTE','CONFIRMADO')) AS reservado
             FROM sucursales s LEFT JOIN existencias e ON e.sucursal_id = s.id AND e.producto_id = :p2
             WHERE s.activo = 1 ORDER BY s.nombre", ['p1' => $productoId, 'p2' => $productoId]);
    }

    public static function marcas(): array
    {
        return array_column(DB::all("SELECT DISTINCT marca_vehiculo FROM productos
            WHERE marca_vehiculo IS NOT NULL AND marca_vehiculo <> '' ORDER BY marca_vehiculo"), 'marca_vehiculo');
    }

    public static function tipos(): array
    {
        return DB::all('SELECT * FROM tipos_producto ORDER BY nombre');
    }

    /** SQL de productos en o por debajo del stock mínimo, por sucursal. */
    public static function sqlStockBajo(?int $sucursalId = null): array
    {
        $sql = "SELECT p.id, p.codigo, p.nombre, p.stock_minimo, s.id AS sucursal_id, s.nombre AS sucursal,
                       COALESCE(e.cantidad,0) AS cantidad
                FROM productos p CROSS JOIN sucursales s
                LEFT JOIN existencias e ON e.producto_id = p.id AND e.sucursal_id = s.id
                WHERE p.activo = 1 AND s.activo = 1 AND p.stock_minimo > 0
                  AND COALESCE(e.cantidad,0) <= p.stock_minimo";
        $p = [];
        if ($sucursalId) { $sql .= ' AND s.id = ?'; $p[] = $sucursalId; }
        return [$sql, $p];
    }

    public static function stockBajo(?int $sucursalId = null, int $limit = 500): array
    {
        [$sql, $p] = self::sqlStockBajo($sucursalId);
        return DB::all("$sql ORDER BY (COALESCE(e.cantidad,0) - p.stock_minimo) ASC, p.nombre LIMIT $limit", $p);
    }

    public static function guardar(array $d, ?int $id = null): int
    {
        $d['actualizado_en'] = date('Y-m-d H:i:s');
        if ($id) {
            DB::update('productos', $d, 'id = :id', ['id' => $id]);
            return $id;
        }
        $d['creado_en'] = date('Y-m-d H:i:s');
        return DB::insert('productos', $d);
    }
}
