<?php
/**
 * Acceso a datos de clientes.
 */
final class Cliente
{
    const TIPOS_DOC = ['V' => 'V - Venezolano', 'E' => 'E - Extranjero', 'J' => 'J - Jurídico', 'G' => 'G - Gobierno', 'P' => 'P - Pasaporte'];

    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM clientes WHERE id = ?', [$id]);
    }

    public static function buscar(string $q, ?int $activo, int $limit = 20, int $offset = 0): array
    {
        $w = '1=1';
        $p = [];
        if ($q !== '') {
            $w .= " AND (nombre LIKE :q1 OR documento LIKE :q2 OR CONCAT(tipo_documento,'-',documento) LIKE :q3 OR telefono LIKE :q4 OR email LIKE :q5)";
            $p['q1'] = $p['q2'] = $p['q3'] = $p['q4'] = $p['q5'] = "%$q%";
        }
        if ($activo !== null) { $w .= ' AND activo = :a'; $p['a'] = $activo; }
        $total = (int)DB::value("SELECT COUNT(*) FROM clientes WHERE $w", $p);
        $rows = DB::all("SELECT c.*, (SELECT COUNT(*) FROM ventas v WHERE v.cliente_id = c.id AND v.estado = 'ACTIVA') AS compras,
                                (SELECT COALESCE(SUM(v.total),0) FROM ventas v WHERE v.cliente_id = c.id AND v.estado = 'ACTIVA') AS total_comprado
                         FROM clientes c WHERE $w ORDER BY nombre LIMIT :lim OFFSET :off",
            array_merge($p, ['lim' => $limit, 'off' => $offset]));
        return [$rows, $total];
    }

    public static function documento(array $c): string
    {
        return $c['tipo_documento'] . '-' . $c['documento'];
    }
}
