<?php
/**
 * Registro de operaciones críticas (auditoría).
 */
final class Bitacora
{
    public static function log(string $modulo, string $accion, string $detalle = '', ?int $usuarioId = -1): void
    {
        try {
            DB::insert('bitacora', [
                'usuario_id' => $usuarioId === -1 ? Auth::id() : $usuarioId,
                'modulo'     => $modulo,
                'accion'     => $accion,
                'detalle'    => mb_substr($detalle, 0, 2000),
                'ip'         => client_ip(),
                'fecha'      => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            error_log('[Cristalia] No se pudo escribir en bitácora: ' . $e->getMessage());
        }
    }
}
