<?php
/**
 * Reglas de negocio del inventario.
 * TODA modificación de existencias pasa por mover(), que bloquea la fila,
 * impide stock negativo y deja el registro en el kardex (movimientos).
 */
final class InventarioService
{
    const TIPOS_MANUALES = ['ENTRADA', 'SALIDA', 'AJUSTE'];

    /**
     * Aplica una variación de existencias. Debe llamarse dentro de DB::transaction().
     * @return int stock resultante
     */
    public static function mover(int $productoId, int $sucursalId, int $delta, string $tipo,
                                 string $motivo = '', ?string $referencia = null): int
    {
        if (!DB::pdo()->inTransaction()) {
            throw new LogicException('InventarioService::mover requiere una transacción activa.');
        }
        $actual = self::bloquear($productoId, $sucursalId);
        $nuevo = $actual + $delta;
        if ($nuevo < 0) {
            $p = Producto::find($productoId);
            throw new AppException(sprintf('Stock insuficiente de "%s" (%s): disponible %d, requerido %d.',
                $p['nombre'] ?? '#' . $productoId, $p['codigo'] ?? '', $actual, abs($delta)));
        }
        DB::run('UPDATE existencias SET cantidad = ?, actualizado_en = ? WHERE producto_id = ? AND sucursal_id = ?',
            [$nuevo, date('Y-m-d H:i:s'), $productoId, $sucursalId]);
        DB::insert('movimientos', [
            'producto_id'      => $productoId,
            'sucursal_id'      => $sucursalId,
            'tipo'             => $tipo,
            'cantidad'         => $delta,
            'stock_anterior'   => $actual,
            'stock_resultante' => $nuevo,
            'motivo'           => mb_substr($motivo, 0, 255),
            'referencia'       => $referencia,
            'usuario_id'       => Auth::id(),
            'fecha'            => date('Y-m-d H:i:s'),
        ]);
        return $nuevo;
    }

    /** Bloquea (SELECT ... FOR UPDATE) la existencia, creándola en 0 si no existe. */
    public static function bloquear(int $productoId, int $sucursalId): int
    {
        $row = DB::one('SELECT cantidad FROM existencias WHERE producto_id = ? AND sucursal_id = ? FOR UPDATE',
            [$productoId, $sucursalId]);
        if ($row === null) {
            DB::run('INSERT INTO existencias (producto_id, sucursal_id, cantidad, actualizado_en) VALUES (?, ?, 0, ?)',
                [$productoId, $sucursalId, date('Y-m-d H:i:s')]);
            return 0;
        }
        return (int)$row['cantidad'];
    }

    /** Registra un movimiento manual (entrada, salida o ajuste a cantidad exacta). */
    public static function registrarManual(int $productoId, int $sucursalId, string $tipo, int $cantidad, string $motivo): int
    {
        if (!in_array($tipo, self::TIPOS_MANUALES, true)) throw new AppException('Tipo de movimiento inválido.');
        if (!Auth::puedeSucursal($sucursalId)) throw new AppException('No puede operar en esa sucursal.');
        $p = Producto::find($productoId);
        if (!$p) throw new AppException('El producto no existe.');
        if (!Sucursal::find($sucursalId)) throw new AppException('La sucursal no existe.');
        if ($tipo !== 'AJUSTE' && $cantidad <= 0) throw new AppException('La cantidad debe ser mayor que cero.');
        if ($tipo === 'AJUSTE' && $cantidad < 0) throw new AppException('La existencia final no puede ser negativa.');
        if ($motivo === '') throw new AppException('Indique el motivo o referencia del movimiento.');

        return DB::transaction(function () use ($productoId, $sucursalId, $tipo, $cantidad, $motivo, $p) {
            if ($tipo === 'AJUSTE') {
                $actual = self::bloquear($productoId, $sucursalId);
                $delta = $cantidad - $actual;
                if ($delta === 0) throw new AppException('La existencia ya es ' . $cantidad . '; no hay nada que ajustar.');
                if ($tipo === 'AJUSTE' && $delta < 0) {
                    // Un ajuste a la baja no puede dejar sin respaldo a pedidos abiertos
                    $reservado = self::reservado($productoId, $sucursalId);
                    if ($cantidad < $reservado) {
                        throw new AppException("No puede ajustar por debajo de $reservado unidades: están reservadas en pedidos abiertos.");
                    }
                }
            } else {
                $delta = $tipo === 'ENTRADA' ? $cantidad : -$cantidad;
                if ($tipo === 'SALIDA') {
                    $disp = self::bloquear($productoId, $sucursalId) - self::reservado($productoId, $sucursalId);
                    if ($cantidad > $disp) {
                        throw new AppException("Sólo hay $disp unidades libres (el resto está reservado en pedidos abiertos).");
                    }
                }
            }
            $nuevo = self::mover($productoId, $sucursalId, $delta, $tipo, $motivo);
            Bitacora::log('inventario', strtolower($tipo),
                sprintf('%s · %s · %+d → %d · %s', $p['codigo'], Sucursal::nombre($sucursalId), $delta, $nuevo, $motivo));
            return $nuevo;
        });
    }

    public static function existencia(int $productoId, int $sucursalId): int
    {
        return (int)(DB::value('SELECT cantidad FROM existencias WHERE producto_id = ? AND sucursal_id = ?',
            [$productoId, $sucursalId]) ?? 0);
    }

    /** Unidades comprometidas en pedidos abiertos (PENDIENTE / CONFIRMADO). */
    public static function reservado(int $productoId, int $sucursalId, ?int $excluirPedido = null): int
    {
        return (int)DB::value(
            "SELECT COALESCE(SUM(d.cantidad),0) FROM pedido_detalle d JOIN pedidos p ON p.id = d.pedido_id
             WHERE d.producto_id = ? AND p.sucursal_id = ? AND p.estado IN ('PENDIENTE','CONFIRMADO') AND p.id <> ?",
            [$productoId, $sucursalId, $excluirPedido ?? 0]);
    }

    public static function disponible(int $productoId, int $sucursalId, ?int $excluirPedido = null): int
    {
        return self::existencia($productoId, $sucursalId) - self::reservado($productoId, $sucursalId, $excluirPedido);
    }

    /** Cantidad de alertas de stock mínimo (para el menú). */
    public static function contarStockBajo(?int $sucursalId = null): int
    {
        [$sql, $p] = Producto::sqlStockBajo($sucursalId);
        return (int)DB::value("SELECT COUNT(*) FROM ($sql) t", $p);
    }
}
