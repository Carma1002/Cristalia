<?php
/**
 * Capa de acceso a datos: conexión PDO única y atajos con sentencias preparadas.
 */
final class DB
{
    private static ?PDO $pdo = null;

    public static function connect(array $c): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $c['host'], (int)($c['port'] ?? 3306), $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // Zona horaria de la sesión de BD alineada con PHP
        $offset = (new DateTime())->format('P');
        $pdo->exec("SET time_zone = '$offset'");
        return $pdo;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect($GLOBALS['config']['db']);
        }
        return self::$pdo;
    }

    public static function setPdo(PDO $pdo): void { self::$pdo = $pdo; }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        foreach ($params as $k => $v) {
            $key = is_int($k) ? $k + 1 : (str_starts_with($k, ':') ? $k : ":$k");
            $type = is_int($v) ? PDO::PARAM_INT : (is_null($v) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $st->bindValue($key, $v, $type);
        }
        $st->execute();
        return $st;
    }

    public static function all(string $sql, array $p = []): array { return self::run($sql, $p)->fetchAll(); }

    public static function one(string $sql, array $p = []): ?array
    {
        $r = self::run($sql, $p)->fetch();
        return $r === false ? null : $r;
    }

    public static function value(string $sql, array $p = []): mixed
    {
        $r = self::run($sql, $p)->fetchColumn();
        return $r === false ? null : $r;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . '`) VALUES (:' . implode(',:', $cols) . ')';
        self::run($sql, $data);
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($c) => "`$c` = :$c", array_keys($data)));
        return self::run("UPDATE `$table` SET $set WHERE $where", array_merge($data, $whereParams))->rowCount();
    }

    /** Ejecuta un bloque dentro de una transacción (rollback automático ante error). */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $r = $fn();
            $pdo->commit();
            return $r;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
