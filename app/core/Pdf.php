<?php
require_once APP_PATH . '/lib/fpdf/fpdf.php';

/**
 * Generador de PDF basado en FPDF (sin dependencias externas).
 */
class Pdf extends FPDF
{
    public string $titulo = '';
    public string $subtitulo = '';

    public function __construct(string $orientacion = 'P')
    {
        if (!defined('FPDF_FONTPATH')) define('FPDF_FONTPATH', APP_PATH . '/lib/fpdf/font/');
        parent::__construct($orientacion, 'mm', 'Letter');
        $this->SetMargins(12, 12, 12);
        $this->SetAutoPageBreak(true, 16);
        $this->AliasNbPages();
        $this->SetTitle($this->t(APP_NAME), false);
    }

    /** Convierte UTF-8 a la codificación de las fuentes estándar de FPDF. */
    public function t(mixed $s): string
    {
        $s = (string)$s;
        $r = @iconv('UTF-8', 'windows-1252//TRANSLIT', $s);
        return $r === false ? mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8') : $r;
    }

    public function Header(): void
    {
        // Encabezado con la identidad del cliente (empresa que usa Cristalia)
        $x = $this->lMargin;
        $logo = logo_cliente();
        if ($logo && is_file(ROOT_PATH . '/' . $logo)) {
            try { $this->Image(ROOT_PATH . '/' . $logo, $x, 10, 13, 13); $x += 16; } catch (Throwable) { /* imagen no soportada */ }
        }
        $this->SetXY($x, 11);
        $this->SetFont('Helvetica', 'B', 12);
        $this->SetTextColor(28, 40, 48);
        $this->Cell(0, 5.5, $this->t(ajuste('empresa_nombre', 'Empresa')), 0, 2, 'L');
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(94, 110, 119);
        $dir = ajuste('empresa_direccion');
        $this->Cell(0, 4, $this->t('RIF ' . ajuste('empresa_rif') . ($dir ? ' · ' . $dir : '')), 0, 1, 'L');
        $this->SetY(26);
        $this->SetFont('Helvetica', 'B', 13);
        $this->SetTextColor(15, 83, 107);
        $this->Cell(0, 6.5, $this->t($this->titulo), 0, 1, 'L');
        if ($this->subtitulo) {
            $this->SetFont('Helvetica', '', 8.5);
            $this->SetTextColor(94, 110, 119);
            $this->Cell(0, 4.5, $this->t($this->subtitulo), 0, 1, 'L');
        }
        $y = $this->GetY() + 2;
        $this->SetFillColor(15, 83, 107);
        $this->Rect($this->lMargin, $y, $this->w - $this->lMargin - $this->rMargin, 0.7, 'F');
        $this->SetFillColor(63, 167, 191);
        $this->Rect($this->lMargin, $y, 28, 0.7, 'F');
        $this->SetY($y + 5);
        $this->SetTextColor(28, 40, 48);
    }

    public function Footer(): void
    {
        $this->SetY(-13);
        $this->SetDrawColor(226, 234, 237);
        $this->SetLineWidth(0.2);
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
        $this->Ln(2);
        $this->SetFont('Helvetica', '', 7.5);
        $this->SetTextColor(94, 110, 119);
        $u = Auth::user();
        $png = ROOT_PATH . '/assets/img/pdf/cristalia-horizontal.png';
        $txt = 'Generado el ' . date('d/m/Y H:i') . ($u ? ' por ' . $u['nombre'] : '') . ' con';
        $this->Cell($this->GetStringWidth($this->t($txt)) + 1.5, 5, $this->t($txt), 0, 0, 'L');
        if (is_file($png)) {
            try { $this->Image($png, $this->GetX(), $this->GetY() + 0.9, 0, 3.4); } catch (Throwable) { $this->Cell(16, 5, 'Cristalia', 0, 0); }
        }
        $this->SetX($this->lMargin);
        $this->Cell(0, 5, $this->t('Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
    }

    public function bloqueDatos(array $pares): void
    {
        $this->SetFont('Helvetica', '', 9);
        $col = ($this->w - $this->lMargin - $this->rMargin) / 2;
        $i = 0;
        foreach ($pares as $k => $v) {
            $this->SetFont('Helvetica', 'B', 9);
            $this->Cell(28, 5.5, $this->t($k . ':'), 0, 0);
            $this->SetFont('Helvetica', '', 9);
            $this->Cell($col - 28, 5.5, $this->t(mb_strimwidth((string)$v, 0, 48, '…')), 0, $i % 2 ? 1 : 0);
            $i++;
        }
        if ($i % 2) $this->Ln();
        $this->Ln(3);
    }

    /**
     * Tabla con encabezado repetido en cada página.
     * @param array $anchos anchos en mm (si se omite se reparten por igual)
     * @param array $alin   'L' | 'R' | 'C' por columna
     */
    public function tabla(array $cab, array $filas, array $anchos = [], array $alin = []): void
    {
        $util = $this->w - $this->lMargin - $this->rMargin;
        if (!$anchos) $anchos = array_fill(0, count($cab), $util / max(1, count($cab)));
        $suma = array_sum($anchos);
        if (abs($suma - $util) > 0.5) $anchos = array_map(fn($a) => $a * $util / $suma, $anchos);

        $pintaCab = function () use ($cab, $anchos) {
            $this->SetFont('Helvetica', 'B', 8.5);
            $this->SetFillColor(15, 83, 107);
            $this->SetTextColor(255, 255, 255);
            foreach ($cab as $i => $c) $this->Cell($anchos[$i], 6.5, $this->t($c), 0, 0, 'C', true);
            $this->Ln();
            $this->SetTextColor(28, 40, 48);
            $this->SetFont('Helvetica', '', 8.5);
        };
        $pintaCab();
        $fill = false;
        foreach ($filas as $f) {
            if ($this->GetY() + 6 > $this->PageBreakTrigger) { $this->AddPage($this->CurOrientation); $pintaCab(); }
            $this->SetFillColor(244, 247, 248);
            foreach (array_values($f) as $i => $v) {
                $txt = $this->t($v);
                // Recorta el texto para que quepa en la celda
                while ($this->GetStringWidth($txt) > $anchos[$i] - 2 && strlen($txt) > 1) $txt = substr($txt, 0, -2) . '.';
                $this->Cell($anchos[$i], 6, $txt, 0, 0, $alin[$i] ?? 'L', $fill);
            }
            $this->Ln();
            $fill = !$fill;
        }
        if (!$filas) {
            $this->SetFont('Helvetica', 'I', 9);
            $this->Cell($util, 8, $this->t('Sin datos para los filtros seleccionados.'), 0, 1, 'C');
        }
        $this->Ln(2);
    }

    public function totales(array $pares): void
    {
        foreach ($pares as $k => $v) {
            $this->SetFont('Helvetica', $k === 'TOTAL' ? 'B' : '', $k === 'TOTAL' ? 11 : 9.5);
            $this->Cell($this->w - $this->lMargin - $this->rMargin - 40, 6.5, $this->t($k), 0, 0, 'R');
            $this->Cell(40, 6.5, $this->t($v), 0, 1, 'R');
        }
        $this->Ln(2);
    }

    public function nota(string $txt): void
    {
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(94, 110, 119);
        $this->MultiCell(0, 4.5, $this->t($txt));
        $this->SetTextColor(28, 40, 48);
        $this->Ln(1);
    }
}
