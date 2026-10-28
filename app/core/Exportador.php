<?php
/**
 * Exporta datos tabulares a PDF o a hoja de cálculo (XLSX).
 */
final class Exportador
{
    /**
     * @param string $formato 'pdf' | 'xlsx'
     * @param array  $columnas [['titulo','clave','tipo'(texto|num|dinero|entero),'ancho'], ...]
     * @param array  $resumen  pares etiqueta => valor que se agregan al final
     */
    public static function enviar(string $formato, string $titulo, string $subtitulo, array $columnas, array $filas,
                                  array $resumen = [], string $orientacion = 'L'): never
    {
        $archivo = preg_replace('/[^a-z0-9]+/', '_', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $titulo) ?: 'reporte')) . '_' . date('Ymd_His');
        Bitacora::log('reportes', 'exportar_' . $formato, $titulo . ' · ' . $subtitulo);

        if ($formato === 'xlsx') {
            require_once APP_PATH . '/lib/SimpleXLSXGen.php';
            $data = [
                ['<b>' . self::xml($titulo) . '</b>'],
                [self::xml($subtitulo)],
                [],
                array_map(fn($c) => '<b>' . self::xml($c[0]) . '</b>', $columnas),
            ];
            foreach ($filas as $f) {
                $data[] = array_map(function ($c) use ($f) {
                    $v = $f[$c[1]] ?? '';
                    return match ($c[2] ?? 'texto') {
                        'num', 'dinero' => round((float)$v, 2),
                        'entero' => (int)$v,
                        default => self::xml((string)$v),
                    };
                }, $columnas);
            }
            if ($resumen) {
                $data[] = [];
                foreach ($resumen as $k => $v) $data[] = ['<b>' . self::xml($k) . '</b>', is_numeric($v) ? $v : self::xml((string)$v)];
            }
            \Shuchkin\SimpleXLSXGen::fromArray($data, 'Reporte')->downloadAs("$archivo.xlsx");
            exit;
        }

        $pdf = new Pdf($orientacion);
        $pdf->titulo = $titulo;
        $pdf->subtitulo = $subtitulo;
        $pdf->AddPage();
        $filasPdf = array_map(fn($f) => array_map(function ($c) use ($f) {
            $v = $f[$c[1]] ?? '';
            return match ($c[2] ?? 'texto') {
                'dinero', 'num' => num($v, 2),
                'entero' => num($v),
                default => (string)$v,
            };
        }, $columnas), $filas);
        $pdf->tabla(array_column($columnas, 0), $filasPdf, array_map(fn($c) => $c[3] ?? 20, $columnas),
            array_map(fn($c) => in_array($c[2] ?? 'texto', ['num', 'dinero', 'entero'], true) ? 'R' : 'L', $columnas));
        if ($resumen) $pdf->totales(array_map(fn($v) => is_int($v) ? num($v) : (is_numeric($v) ? num($v, 2) : (string)$v), $resumen));
        $pdf->Output('I', "$archivo.pdf");
        exit;
    }

    /** Escapa caracteres que SimpleXLSXGen interpretaría como etiquetas. */
    private static function xml(string $s): string
    {
        return str_replace(['<', '>'], ['&lt;', '&gt;'], $s);
    }
}
