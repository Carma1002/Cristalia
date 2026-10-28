<?php
final class SucursalController extends Controller
{
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
        redirect('empresa');
    }
}
