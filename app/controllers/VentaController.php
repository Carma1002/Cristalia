<?php
final class VentaController extends Controller
{
    public function index(): void
    {
        [$lim, $off, $page] = paginar(25);
        $f = [
            'q' => input('q', ''), 'estado' => input('estado', ''), 'sucursal_id' => $this->sucursalFiltro(),
            'desde' => input('desde', date('Y-m-01')), 'hasta' => input('hasta', date('Y-m-d')),
        ];
        [$rows, $total, $monto] = Venta::buscar($f, $lim, $off);
        $this->view('ventas/index', [
            'title' => 'Ventas', 'rows' => $rows, 'total' => $total, 'monto' => $monto, 'page' => $page, 'lim' => $lim,
            'f' => $f, 'sucursales' => Sucursal::delUsuario(),
        ]);
    }

    public function ver(): void
    {
        $v = $this->cargar();
        $this->view('ventas/ver', ['title' => 'Venta ' . $v['numero'], 'v' => $v, 'det' => Venta::detalle((int)$v['id'])]);
    }

    /** Formulario para convertir un pedido abierto en venta. */
    public function form(): void
    {
        $p = $this->found(Pedido::find((int)input_int('pedido_id')), 'El pedido');
        if (!in_array($p['estado'], ['PENDIENTE', 'CONFIRMADO'], true)) {
            throw new AppException("El pedido {$p['numero']} está {$p['estado']}.");
        }
        $this->view('ventas/form', ['title' => 'Registrar venta', 'p' => $p, 'det' => Pedido::detalle((int)$p['id'])]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $id = VentaService::registrar((int)input_int('pedido_id', 0), (string)input('metodo_pago', ''), input_dec('descuento'));
        flash('success', 'Venta registrada. El inventario fue descontado automáticamente.');
        redirect('ventas/ver', ['id' => $id]);
    }

    public function anular(): void
    {
        $this->requirePost();
        $id = (int)input_int('id', 0);
        VentaService::anular($id, (string)input('motivo', ''));
        flash('success', 'Venta anulada. Las unidades regresaron al inventario.');
        redirect('ventas/ver', ['id' => $id]);
    }

    /** Comprobante imprimible (HTML). */
    public function comprobante(): void
    {
        $v = $this->cargar();
        View::render('ventas/comprobante', ['v' => $v, 'det' => Venta::detalle((int)$v['id'])], '');
    }

    /** Comprobante en PDF. */
    public function comprobantePdf(): void
    {
        $v = $this->cargar();
        $det = Venta::detalle((int)$v['id']);
        $pdf = new Pdf('P');
        $pdf->titulo = 'Comprobante de venta ' . $v['numero'];
        $pdf->subtitulo = 'Documento administrativo interno · NO VÁLIDO COMO FACTURA FISCAL';
        $pdf->AddPage();
        $pdf->bloqueDatos([
            'Comprobante' => $v['numero'] . ($v['estado'] === 'ANULADA' ? '  (ANULADA)' : ''),
            'Fecha' => fecha($v['fecha']),
            'Pedido' => $v['pedido_numero'],
            'Sucursal' => $v['sucursal'],
            'Cliente' => $v['cliente'] . ' · ' . $v['cliente_doc'],
            'Teléfono' => $v['cliente_tel'] ?: '—',
            'Método de pago' => $v['metodo_pago'],
            'Atendido por' => $v['usuario'] ?: '—',
        ]);
        $rows = array_map(fn($d) => [$d['codigo'], $d['nombre'], $d['cantidad'], num($d['precio_unitario'], 2), num($d['subtotal'], 2)], $det);
        $pdf->tabla(['Código', 'Descripción', 'Cant.', 'P. unit.', 'Subtotal'], $rows, [30, 85, 15, 27, 30], ['L', 'L', 'R', 'R', 'R']);
        $pdf->totales([
            'Subtotal' => money($v['subtotal']),
            'Descuento' => money($v['descuento']),
            'TOTAL' => money($v['total']),
        ]);
        if ($v['estado'] === 'ANULADA') $pdf->nota('Venta anulada. Motivo: ' . $v['motivo_anulacion']);
        $pdf->nota('Este comprobante es de uso administrativo interno de ' . ajuste('empresa_nombre') . ' y no sustituye la factura fiscal.');
        Bitacora::log('ventas', 'comprobante_pdf', $v['numero']);
        $pdf->Output('I', $v['numero'] . '.pdf');
    }

    private function cargar(): array
    {
        $v = $this->found(Venta::find((int)input_int('id')), 'La venta');
        if (!Auth::puedeSucursal((int)$v['sucursal_id'])) throw new AppException('La venta pertenece a otra sucursal.');
        return $v;
    }
}
