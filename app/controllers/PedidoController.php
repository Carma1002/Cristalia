<?php
final class PedidoController extends Controller
{
    public function index(): void
    {
        [$lim, $off, $page] = paginar(25);
        $f = [
            'q' => input('q', ''), 'estado' => input('estado', ''), 'sucursal_id' => $this->sucursalFiltro(),
            'desde' => input('desde', ''), 'hasta' => input('hasta', ''),
        ];
        [$rows, $total] = Pedido::buscar($f, $lim, $off);
        $this->view('pedidos/index', [
            'title' => 'Pedidos', 'rows' => $rows, 'total' => $total, 'page' => $page, 'lim' => $lim, 'f' => $f,
            'sucursales' => Sucursal::delUsuario(), 'conteo' => Pedido::contarPorEstado($f['sucursal_id']),
        ]);
    }

    public function ver(): void
    {
        $p = $this->found(Pedido::find((int)input_int('id')), 'El pedido');
        if (!Auth::puedeSucursal((int)$p['sucursal_id'])) throw new AppException('El pedido pertenece a otra sucursal.');
        $det = Pedido::detalle((int)$p['id']);
        // Disponibilidad actual de cada línea (para pedidos abiertos)
        if (in_array($p['estado'], ['PENDIENTE', 'CONFIRMADO'], true)) {
            foreach ($det as &$d) {
                $d['disponible'] = InventarioService::disponible((int)$d['producto_id'], (int)$p['sucursal_id'], (int)$p['id']);
            }
            unset($d);
        }
        $this->view('pedidos/ver', [
            'title' => 'Pedido ' . $p['numero'], 'p' => $p, 'det' => $det, 'hist' => Pedido::historial((int)$p['id']),
            'transiciones' => PedidoService::TRANSICIONES[$p['estado']] ?? [],
        ]);
    }

    public function form(): void
    {
        $cid = input_int('cliente_id') ?? (int)old('cliente_id', 0);
        // Si el guardado falló, se reconstruyen las líneas que el usuario ya había cargado
        $prefill = [];
        $suc = (int)old('sucursal_id', Auth::sucursalFija() ?? 0);
        foreach ((array)old('producto_id', []) as $i => $pid) {
            $p = Producto::find((int)$pid);
            if (!$p) continue;
            $prefill[] = ['p' => [
                'id' => (int)$p['id'], 'codigo' => $p['codigo'], 'nombre' => $p['nombre'], 'precio' => (float)$p['precio'],
                'disponible' => $suc ? InventarioService::disponible((int)$p['id'], $suc) : 0,
            ], 'cant' => max(1, (int)(((array)old('cantidad', []))[$i] ?? 1))];
        }
        $this->view('pedidos/form', [
            'title' => 'Nuevo pedido', 'cliente' => $cid ? Cliente::find($cid) : null, 'prefill' => $prefill,
            'sucursales' => Sucursal::delUsuario(), 'producto' => input_int('producto_id') ? Producto::find((int)input_int('producto_id')) : null,
        ]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $items = [];
        $pids = (array)($_POST['producto_id'] ?? []);
        $cants = (array)($_POST['cantidad'] ?? []);
        foreach ($pids as $i => $pid) $items[] = ['producto_id' => (int)$pid, 'cantidad' => (int)($cants[$i] ?? 0)];
        $id = PedidoService::crear((int)input_int('cliente_id', 0), (int)input_int('sucursal_id', 0), $items, (string)input('observaciones', ''));
        flash('success', 'Pedido creado. Las unidades quedaron apartadas hasta que se registre la venta o se cancele.');
        redirect('pedidos/ver', ['id' => $id]);
    }

    public function estado(): void
    {
        $this->requirePost();
        $id = (int)input_int('id', 0);
        $nuevo = (string)input('estado', '');
        PedidoService::cambiarEstado($id, $nuevo, (string)input('nota', ''));
        flash('success', ['CONFIRMADO' => 'Pedido confirmado. Ya puede registrar la venta cuando el cliente pague.',
                          'CANCELADO' => 'Pedido cancelado. Las unidades apartadas volvieron a estar disponibles.',
                          'PENDIENTE' => 'El pedido volvió a quedar pendiente.'][$nuevo] ?? 'Pedido actualizado.');
        redirect('pedidos/ver', ['id' => $id]);
    }
}
