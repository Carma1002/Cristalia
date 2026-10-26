<?php
final class ClienteController extends Controller
{
    public function index(): void
    {
        [$lim, $off, $page] = paginar(25);
        $activo = input('activo', '1');
        [$rows, $total] = Cliente::buscar((string)input('q', ''), $activo === '' ? null : (int)$activo, $lim, $off);
        $this->view('clientes/index', [
            'title' => 'Clientes', 'rows' => $rows, 'total' => $total, 'page' => $page, 'lim' => $lim,
            'q' => input('q', ''), 'activo' => $activo,
        ]);
    }

    public function ver(): void
    {
        $c = $this->found(Cliente::find((int)input_int('id')), 'El cliente');
        $this->view('clientes/ver', [
            'title' => $c['nombre'], 'c' => $c,
            'pedidos' => Pedido::buscar(['cliente_id' => $c['id']], 50)[0],
            'ventas' => Venta::buscar(['cliente_id' => $c['id']], 50),
        ]);
    }

    public function form(): void
    {
        $id = input_int('id');
        $c = $id ? $this->found(Cliente::find($id), 'El cliente') : null;
        $this->view('clientes/form', ['title' => $c ? 'Editar cliente' : 'Nuevo cliente', 'c' => $c, 'volver' => input('volver', '')]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $id = input_int('id');
        if ($id) $this->found(Cliente::find($id), 'El cliente');
        $d = [
            'tipo_documento' => strtoupper((string)input('tipo_documento', 'V')),
            'documento'      => preg_replace('/[^0-9A-Za-z]/', '', (string)input('documento', '')),
            'nombre'         => (string)input('nombre', ''),
            'telefono'       => (string)input('telefono', '') ?: null,
            'email'          => (string)input('email', '') ?: null,
            'direccion'      => (string)input('direccion', '') ?: null,
            'activo'         => input('activo', '1') === '1' ? 1 : 0,
        ];
        $err = [];
        if (!array_key_exists($d['tipo_documento'], Cliente::TIPOS_DOC)) $err[] = 'Tipo de documento inválido.';
        if (!preg_match('/^[0-9A-Za-z]{5,15}$/', $d['documento'])) $err[] = 'Documento inválido (5 a 15 caracteres).';
        if (mb_strlen($d['nombre']) < 3) $err[] = 'Indique el nombre o razón social.';
        if ($d['email'] && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $err[] = 'Correo electrónico inválido.';
        $dup = DB::value('SELECT id FROM clientes WHERE tipo_documento = ? AND documento = ? AND id <> ?', [$d['tipo_documento'], $d['documento'], $id ?? 0]);
        if ($dup) $err[] = "Ya existe un cliente con el documento {$d['tipo_documento']}-{$d['documento']}.";
        if ($err) throw new AppException(implode("\n", $err));

        if ($id) {
            DB::update('clientes', $d, 'id = :id', ['id' => $id]);
            Bitacora::log('clientes', 'editar', "{$d['tipo_documento']}-{$d['documento']} · {$d['nombre']}");
        } else {
            $d['creado_en'] = date('Y-m-d H:i:s');
            $id = DB::insert('clientes', $d);
            Bitacora::log('clientes', 'crear', "{$d['tipo_documento']}-{$d['documento']} · {$d['nombre']}");
        }
        flash('success', 'Cliente guardado.');
        if (input('volver') === 'pedido') redirect('pedidos/crear', ['cliente_id' => $id]);
        redirect('clientes/ver', ['id' => $id]);
    }

    public function buscarJson(): void
    {
        [$rows] = Cliente::buscar((string)input('q', ''), 1, 15, 0);
        $this->json(array_map(fn($c) => [
            'id' => (int)$c['id'], 'nombre' => $c['nombre'], 'documento' => Cliente::documento($c), 'telefono' => $c['telefono'],
        ], $rows));
    }
}
