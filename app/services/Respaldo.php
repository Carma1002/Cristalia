<?php
/**
 * Copias de seguridad y restauración de la base de datos en SQL puro
 * (no requiere mysqldump, funciona en hosting compartido).
 */
final class Respaldo
{
    const FIRMA = '-- Cristalia respaldo';

    public static function dir(): string
    {
        $d = APP_PATH . '/storage/backups';
        if (!is_dir($d)) mkdir($d, 0755, true);
        return $d;
    }

    public static function listar(): array
    {
        $out = [];
        foreach (glob(self::dir() . '/*.sql') ?: [] as $f) {
            $out[] = ['archivo' => basename($f), 'tamano' => filesize($f), 'fecha' => date('Y-m-d H:i:s', filemtime($f))];
        }
        usort($out, fn($a, $b) => strcmp($b['archivo'], $a['archivo']));
        return $out;
    }

    public static function ruta(string $archivo): string
    {
        if (!preg_match('/^[A-Za-z0-9_\-]+\.sql$/', $archivo)) throw new AppException('Nombre de archivo inválido.');
        $f = self::dir() . '/' . $archivo;
        if (!is_file($f)) throw new AppException('El respaldo no existe.');
        return $f;
    }

    /** Genera un respaldo completo y devuelve el nombre del archivo. */
    public static function generar(string $etiqueta = 'manual'): string
    {
        $pdo = DB::pdo();
        $tablas = array_column(DB::all('SHOW FULL TABLES'), null);
        $nombres = [];
        foreach ($tablas as $t) {
            $vals = array_values($t);
            if (($vals[1] ?? 'BASE TABLE') === 'BASE TABLE') $nombres[] = $vals[0];
        }
        $archivo = 'respaldo_' . date('Ymd_His') . '_' . preg_replace('/[^a-z0-9]/', '', $etiqueta) . '.sql';
        $fh = fopen(self::dir() . '/' . $archivo, 'w');
        fwrite($fh, self::FIRMA . ' · ' . date('Y-m-d H:i:s') . ' · v' . APP_VERSION . "\n");
        fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");
        foreach ($nombres as $t) {
            $create = DB::one("SHOW CREATE TABLE `$t`");
            fwrite($fh, "DROP TABLE IF EXISTS `$t`;\n" . array_values($create)[1] . ";\n\n");
            $st = $pdo->query("SELECT * FROM `$t`");
            $lote = [];
            $cols = null;
            while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                $cols ??= '`' . implode('`,`', array_keys($row)) . '`';
                $lote[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $row)) . ')';
                if (count($lote) >= 200) { fwrite($fh, "INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $lote) . ";\n"); $lote = []; }
            }
            if ($lote) fwrite($fh, "INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $lote) . ";\n");
            fwrite($fh, "\n");
        }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($fh);
        return $archivo;
    }

    /** Restaura desde un archivo .sql generado por Cristalia. */
    public static function restaurar(string $ruta): int
    {
        $sql = file_get_contents($ruta);
        if ($sql === false || !str_starts_with($sql, self::FIRMA)) {
            throw new AppException('El archivo no es un respaldo válido de ' . APP_NAME . '.');
        }
        $n = 0;
        $pdo = DB::pdo();
        foreach (self::dividir($sql) as $stmt) { $pdo->exec($stmt); $n++; }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        return $n;
    }

    /** Divide un script SQL en sentencias respetando comillas y comentarios. */
    public static function dividir(string $sql): array
    {
        $out = [];
        $buf = '';
        $len = strlen($sql);
        $q = null;
        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            if ($q === null) {
                if ($c === '-' && ($sql[$i + 1] ?? '') === '-' ) { $i = strpos($sql, "\n", $i) ?: $len; continue; }
                if ($c === '#') { $i = strpos($sql, "\n", $i) ?: $len; continue; }
                if ($c === '/' && ($sql[$i + 1] ?? '') === '*') { $i = (strpos($sql, '*/', $i + 2) ?: $len) + 1; continue; }
                if ($c === "'" || $c === '"' || $c === '`') $q = $c;
                if ($c === ';') { if (trim($buf) !== '') $out[] = trim($buf); $buf = ''; continue; }
            } else {
                if ($c === '\\' && $q !== '`') { $buf .= $c . ($sql[$i + 1] ?? ''); $i++; continue; }
                if ($c === $q) {
                    if (($sql[$i + 1] ?? '') === $q) { $buf .= $c . $c; $i++; continue; }
                    $q = null;
                }
            }
            $buf .= $c;
        }
        if (trim($buf) !== '') $out[] = trim($buf);
        return $out;
    }
}
