<?php
final class CatalogoController extends Controller
{
    public function index(): void
    {
        [$lim, $off, $page] = paginar(24);
        $f = [
            'q' => input('q', ''), 'tipo_id' => input_int('tipo_id'), 'marca' => input('marca', ''),
            'modelo' => input('modelo', ''), 'anio' => input_int('anio'), 'activo' => '1',
            'sucursal_id' => input_int('sucursal_id'), 'solo_disponibles' => input('disp') ? 1 : 0,
            'orden' => input('orden', ''),
        ];
        [$rows, $total] = Producto::buscar($f, $lim, $off);

        // Disponibilidad por sucursal de los productos mostrados
        $disp = [];
        if ($rows) {
            $in = implode(',', array_map(fn($r) => (int)$r['id'], $rows));
            foreach (DB::all("SELECT e.producto_id, s.nombre, e.cantidad FROM existencias e
                              JOIN sucursales s ON s.id = e.sucursal_id AND s.activo = 1
                              WHERE e.producto_id IN ($in) ORDER BY s.nombre") as $e) {
                $disp[$e['producto_id']][] = $e;
            }
        }
        $this->view('catalogo/index', [
            'title' => 'Catálogo digital', 'rows' => $rows, 'total' => $total, 'page' => $page, 'lim' => $lim,
            'f' => $f, 'disp' => $disp, 'tipos' => Producto::tipos(), 'marcas' => Producto::marcas(),
            'sucursales' => Sucursal::todas(),
        ]);
    }
}
