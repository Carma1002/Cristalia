<?php
final class SucursalController extends Controller
{
    public function index(): void
    {
        $rows = DB::all("SELECT s.*,
            (SELECT COUNT(*) FROM usuarios u WHERE u.sucursal_id = s.id AND u.activo = 1) AS usuarios,
            (SELECT COALESCE(SUM(e.cantidad),0) FROM existencias e WHERE e.sucursal_id = s.id) AS unidades,
            (SELECT COUNT(*) FROM pedidos p WHERE p.sucursal_id = s.id AND p.estado IN ('PENDIENTE','CONFIRMADO')) AS pedidos_abiertos,
            (SELECT COALESCE(SUM(v.total),0) FROM ventas v WHERE v.sucursal_id = s.id AND v.estado = 'ACTIVA' AND v.fecha >= :ini) AS ventas_mes
            FROM sucursales s ORDER BY s.nombre", ['ini' => date('Y-m-01 00:00:00')]);
        $this->view('admin/sucursales', ['title' => 'Sucursales', 'rows' => $rows]);
    }

    public function form(): void
    {
        $id = input_int('id');
        $s = $id ? $this->found(Sucursal::find($id), 'La sucursal') : null;
        $this->view('admin/sucursal_form', ['title' => $s ? 'Editar sucursal' : 'Nueva sucursal', 's' => $s]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $id = input_int('id');
        $actual = $id ? $this->found(Sucursal::find($id), 'La sucursal') : null;
        $d = [
            'codigo'    => strtoupper((string)input('codigo', '')),
            'nombre'    => (string)input('nombre', ''),
            'direccion' => (string)input('direccion', '') ?: null,
            'telefono'  => (string)input('telefono', '') ?: null,
            'activo'    => input('activo', '1') === '1' ? 1 : 0,
        ];
        $err = [];
        if (!preg_match('/^[A-Z0-9]{2,10}$/', $d['codigo'])) $err[] = 'Código inválido (2-10 letras/números).';
        if (mb_strlen($d['nombre']) < 3) $err[] = 'Indique el nombre de la sucursal.';
        if (DB::value('SELECT id FROM sucursales WHERE codigo = ? AND id <> ?', [$d['codigo'], $id ?? 0])) $err[] = 'Ya existe una sucursal con ese código.';
        if ($actual && $actual['activo'] && !$d['activo']) {
            if (Pedido::contarAbiertos($id)) $err[] = 'No puede desactivar una sucursal con pedidos abiertos.';
            if ((int)DB::value('SELECT COUNT(*) FROM sucursales WHERE activo = 1') <= 1) $err[] = 'Debe quedar al menos una sucursal activa.';
        }
        if ($err) throw new AppException(implode("\n", $err));
        if ($id) DB::update('sucursales', $d, 'id = :id', ['id' => $id]);
        else { $d['creado_en'] = date('Y-m-d H:i:s'); $id = DB::insert('sucursales', $d); }
        Bitacora::log('administracion', $actual ? 'sucursal_editar' : 'sucursal_crear', "{$d['codigo']} · {$d['nombre']}");
        flash('success', 'Sucursal guardada.');
        redirect('sucursales');
    }
}
