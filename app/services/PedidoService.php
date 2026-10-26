<?php
/**
 * Reglas de negocio de pedidos:
 *  - Al crear o confirmar se verifica la disponibilidad (existencia − reservado por otros pedidos abiertos).
 *  - Los pedidos abiertos (PENDIENTE/CONFIRMADO) reservan existencias.
 *  - COMPLETADO sólo se alcanza registrando la venta (VentaService).
 */
final class PedidoService
{
    /** Transiciones manuales permitidas. */
    const TRANSICIONES = [
        'PENDIENTE'  => ['CONFIRMADO', 'CANCELADO'],
        'CONFIRMADO' => ['PENDIENTE', 'CANCELADO'],
        'COMPLETADO' => [],
        'CANCELADO'  => [],
    ];

    /**
     * @param array $items lista de ['producto_id' => int, 'cantidad' => int]
     */
    public static function crear(int $clienteId, int $sucursalId, array $items, string $obs = ''): int
    {
        $cliente = Cliente::find($clienteId);
        if (!$cliente || !$cliente['activo']) throw new AppException('Seleccione un cliente activo.');
        $suc = Sucursal::find($sucursalId);
        if (!$suc || !$suc['activo']) throw new AppException('Seleccione una sucursal activa.');
        if (!Auth::puedeSucursal($sucursalId)) throw new AppException('No puede crear pedidos en esa sucursal.');

        // Normalizar: sumar cantidades de productos repetidos
        $lineas = [];
        foreach ($items as $it) {
            $pid = (int)($it['producto_id'] ?? 0);
            $cant = (int)($it['cantidad'] ?? 0);
            if ($pid <= 0) continue;
            if ($cant <= 0) throw new AppException('Las cantidades deben ser mayores que cero.');
            $lineas[$pid] = ($lineas[$pid] ?? 0) + $cant;
        }
        if (!$lineas) throw new AppException('Agregue al menos un producto al pedido.');
        if (count($lineas) > 100) throw new AppException('Máximo 100 productos por pedido.');

        return DB::transaction(function () use ($clienteId, $sucursalId, $lineas, $obs) {
            $errores = [];
            $detalle = [];
            $total = 0.0;
            ksort($lineas); // orden fijo de bloqueo para evitar interbloqueos
            foreach ($lineas as $pid => $cant) {
                $p = Producto::find($pid);
                if (!$p || !$p['activo']) { $errores[] = "El producto #$pid no existe o está inactivo."; continue; }
                $existencia = InventarioService::bloquear($pid, $sucursalId);
                $disp = $existencia - InventarioService::reservado($pid, $sucursalId);
                if ($cant > $disp) {
                    $errores[] = sprintf('%s (%s): solicitado %d, disponible %d.', $p['nombre'], $p['codigo'], $cant, max(0, $disp));
                    continue;
                }
                $sub = round($cant * (float)$p['precio'], 2);
                $total += $sub;
                $detalle[] = ['producto_id' => $pid, 'cantidad' => $cant, 'precio_unitario' => $p['precio'], 'subtotal' => $sub];
            }
            if ($errores) throw new AppException("No hay existencias suficientes:\n• " . implode("\n• ", $errores));

            $id = DB::insert('pedidos', [
                'cliente_id' => $clienteId, 'sucursal_id' => $sucursalId, 'usuario_id' => Auth::id(),
                'estado' => 'PENDIENTE', 'total' => round($total, 2), 'observaciones' => mb_substr($obs, 0, 255),
                'fecha' => date('Y-m-d H:i:s'),
            ]);
            $numero = 'P-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
            DB::update('pedidos', ['numero' => $numero], 'id = :id', ['id' => $id]);
            foreach ($detalle as $d) DB::insert('pedido_detalle', $d + ['pedido_id' => $id]);
            self::historial($id, null, 'PENDIENTE', 'Pedido creado');
            Bitacora::log('pedidos', 'crear', "$numero · " . count($detalle) . ' productos · total ' . number_format($total, 2));
            return $id;
        });
    }

    public static function cambiarEstado(int $id, string $nuevo, string $nota = ''): void
    {
        DB::transaction(function () use ($id, $nuevo, $nota) {
            $p = DB::one('SELECT * FROM pedidos WHERE id = ? FOR UPDATE', [$id]);
            if (!$p) throw new AppException('El pedido no existe.');
            if (!Auth::puedeSucursal((int)$p['sucursal_id'])) throw new AppException('No puede operar pedidos de otra sucursal.');
            $permitidos = self::TRANSICIONES[$p['estado']] ?? [];
            if (!in_array($nuevo, $permitidos, true)) {
                throw new AppException("No se puede pasar de {$p['estado']} a $nuevo.");
            }
            if ($nuevo === 'CANCELADO' && trim($nota) === '') throw new AppException('Indique el motivo de la cancelación.');
            if ($nuevo === 'CONFIRMADO') self::verificarDisponibilidad($id, (int)$p['sucursal_id']);

            DB::update('pedidos', ['estado' => $nuevo], 'id = :id', ['id' => $id]);
            self::historial($id, $p['estado'], $nuevo, $nota);
            Bitacora::log('pedidos', 'estado', "{$p['numero']}: {$p['estado']} → $nuevo" . ($nota ? " ($nota)" : ''));
        });
    }

    /** Revalida que todas las líneas del pedido sigan teniendo existencia (con bloqueo de filas). */
    public static function verificarDisponibilidad(int $pedidoId, int $sucursalId): void
    {
        $errores = [];
        $det = DB::all('SELECT d.producto_id, d.cantidad, p.nombre, p.codigo FROM pedido_detalle d
                        JOIN productos p ON p.id = d.producto_id WHERE d.pedido_id = ? ORDER BY d.producto_id', [$pedidoId]);
        foreach ($det as $d) {
            $ex = InventarioService::bloquear((int)$d['producto_id'], $sucursalId);
            $disp = $ex - InventarioService::reservado((int)$d['producto_id'], $sucursalId, $pedidoId);
            if ((int)$d['cantidad'] > $disp) {
                $errores[] = sprintf('%s (%s): requiere %d, disponible %d.', $d['nombre'], $d['codigo'], $d['cantidad'], max(0, $disp));
            }
        }
        if ($errores) throw new AppException("Existencias insuficientes:\n• " . implode("\n• ", $errores));
    }

    public static function historial(int $pedidoId, ?string $anterior, string $nuevo, string $nota = ''): void
    {
        DB::insert('pedido_historial', [
            'pedido_id' => $pedidoId, 'estado_anterior' => $anterior, 'estado_nuevo' => $nuevo,
            'nota' => mb_substr($nota, 0, 255), 'usuario_id' => Auth::id(), 'fecha' => date('Y-m-d H:i:s'),
        ]);
    }
}
