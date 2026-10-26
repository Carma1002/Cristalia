<?php
/**
 * Reglas de negocio de ventas:
 *  - Una venta se registra siempre a partir de un pedido abierto (1 pedido = 1 venta).
 *  - En la misma transacción: se descuenta el inventario (kardex tipo VENTA),
 *    se crea la venta con su detalle y el pedido pasa a COMPLETADO.
 *  - La anulación devuelve las unidades al inventario (kardex tipo ANULACION).
 *  - El comprobante es de uso administrativo interno, NO es una factura fiscal.
 */
final class VentaService
{
    public static function registrar(int $pedidoId, string $metodoPago, float $descuento = 0): int
    {
        if (!in_array($metodoPago, Venta::METODOS_PAGO, true)) throw new AppException('Seleccione un método de pago válido.');
        if ($descuento < 0) throw new AppException('El descuento no puede ser negativo.');

        return DB::transaction(function () use ($pedidoId, $metodoPago, $descuento) {
            $p = DB::one('SELECT * FROM pedidos WHERE id = ? FOR UPDATE', [$pedidoId]);
            if (!$p) throw new AppException('El pedido no existe.');
            if (!Auth::puedeSucursal((int)$p['sucursal_id'])) throw new AppException('No puede facturar pedidos de otra sucursal.');
            if (!in_array($p['estado'], ['PENDIENTE', 'CONFIRMADO'], true)) {
                throw new AppException("El pedido {$p['numero']} está {$p['estado']} y no puede convertirse en venta.");
            }
            if (DB::value('SELECT id FROM ventas WHERE pedido_id = ?', [$pedidoId])) {
                throw new AppException('Este pedido ya tiene una venta registrada.');
            }
            $det = DB::all('SELECT * FROM pedido_detalle WHERE pedido_id = ? ORDER BY producto_id', [$pedidoId]);
            if (!$det) throw new AppException('El pedido no tiene productos.');

            $subtotal = round(array_sum(array_map(fn($d) => (float)$d['subtotal'], $det)), 2);
            if ($descuento > $subtotal) throw new AppException('El descuento no puede superar el subtotal.');
            $total = round($subtotal - $descuento, 2);

            $ventaId = DB::insert('ventas', [
                'pedido_id' => $pedidoId, 'cliente_id' => $p['cliente_id'], 'sucursal_id' => $p['sucursal_id'],
                'usuario_id' => Auth::id(), 'subtotal' => $subtotal, 'descuento' => round($descuento, 2),
                'total' => $total, 'metodo_pago' => $metodoPago, 'estado' => 'ACTIVA', 'fecha' => date('Y-m-d H:i:s'),
            ]);
            $numero = 'CI-' . str_pad((string)$ventaId, 6, '0', STR_PAD_LEFT);
            DB::update('ventas', ['numero' => $numero], 'id = :id', ['id' => $ventaId]);

            foreach ($det as $d) {
                // Descuento automático del inventario (lanza error si no alcanza → rollback total)
                InventarioService::mover((int)$d['producto_id'], (int)$p['sucursal_id'], -(int)$d['cantidad'],
                    'VENTA', "Venta $numero (pedido {$p['numero']})", $numero);
                DB::insert('venta_detalle', [
                    'venta_id' => $ventaId, 'producto_id' => $d['producto_id'], 'cantidad' => $d['cantidad'],
                    'precio_unitario' => $d['precio_unitario'], 'subtotal' => $d['subtotal'],
                ]);
            }
            DB::update('pedidos', ['estado' => 'COMPLETADO'], 'id = :id', ['id' => $pedidoId]);
            PedidoService::historial($pedidoId, $p['estado'], 'COMPLETADO', "Venta $numero registrada");
            Bitacora::log('ventas', 'registrar', "$numero · pedido {$p['numero']} · total " . number_format($total, 2) . " · $metodoPago");
            return $ventaId;
        });
    }

    public static function anular(int $ventaId, string $motivo): void
    {
        if (trim($motivo) === '') throw new AppException('Indique el motivo de la anulación.');
        DB::transaction(function () use ($ventaId, $motivo) {
            $v = DB::one('SELECT * FROM ventas WHERE id = ? FOR UPDATE', [$ventaId]);
            if (!$v) throw new AppException('La venta no existe.');
            if ($v['estado'] !== 'ACTIVA') throw new AppException('La venta ya está anulada.');
            if (!Auth::puedeSucursal((int)$v['sucursal_id'])) throw new AppException('No puede anular ventas de otra sucursal.');

            foreach (DB::all('SELECT * FROM venta_detalle WHERE venta_id = ? ORDER BY producto_id', [$ventaId]) as $d) {
                InventarioService::mover((int)$d['producto_id'], (int)$v['sucursal_id'], (int)$d['cantidad'],
                    'ANULACION', "Anulación de {$v['numero']}: $motivo", $v['numero']);
            }
            DB::update('ventas', ['estado' => 'ANULADA', 'motivo_anulacion' => mb_substr($motivo, 0, 255)], 'id = :id', ['id' => $ventaId]);
            $pedEstado = DB::value('SELECT estado FROM pedidos WHERE id = ?', [$v['pedido_id']]);
            DB::update('pedidos', ['estado' => 'CANCELADO'], 'id = :id', ['id' => $v['pedido_id']]);
            PedidoService::historial((int)$v['pedido_id'], $pedEstado, 'CANCELADO', "Venta {$v['numero']} anulada: $motivo");
            Bitacora::log('ventas', 'anular', "{$v['numero']} · $motivo");
        });
    }
}
