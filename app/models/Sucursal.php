<?php
/**
 * Acceso a datos de sucursales.
 */
final class Sucursal
{
    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM sucursales WHERE id = ?', [$id]);
    }

    public static function todas(bool $soloActivas = true): array
    {
        return DB::all('SELECT * FROM sucursales' . ($soloActivas ? ' WHERE activo = 1' : '') . ' ORDER BY nombre');
    }

    /** Sucursales que el usuario actual puede usar. */
    public static function delUsuario(): array
    {
        $f = Auth::sucursalFija();
        return $f ? DB::all('SELECT * FROM sucursales WHERE id = ?', [$f]) : self::todas();
    }

    public static function nombre(int $id): string
    {
        static $cache = [];
        return $cache[$id] ??= (string)(DB::value('SELECT nombre FROM sucursales WHERE id = ?', [$id]) ?? '#' . $id);
    }
}
