<?php
/**
 * Datos de demostración (opcionales) para probar el sistema.
 * Genera sucursales, usuarios, productos, clientes y 12 meses de historia
 * usando los mismos servicios de negocio que la aplicación (respetan stock y kardex).
 */
function cargar_demo(string $claveDemo): void
{
    mt_srand(2026);
    $ahora = time();

    // Sucursales adicionales (la principal ya la crea el instalador)
    $sucPrincipal = (int)DB::value('SELECT id FROM sucursales ORDER BY id LIMIT 1');
    $sucVal = DB::insert('sucursales', ['codigo' => 'VAL', 'nombre' => 'Sucursal Demo 2', 'direccion' => 'Dirección de demostración', 'telefono' => null]);
    $sucMcy = DB::insert('sucursales', ['codigo' => 'MCY', 'nombre' => 'Sucursal Demo 3', 'direccion' => 'Dirección de demostración', 'telefono' => null]);
    $sucursales = [$sucPrincipal, $sucVal, $sucMcy];

    // Usuarios de demostración: la clave se genera aleatoriamente durante la instalación.
    $hash = password_hash($claveDemo, PASSWORD_DEFAULT);
    foreach ([
        ['Usuario Demo Ventas', 'ventas', 2, $sucPrincipal],
        ['Usuario Demo Almacén', 'almacen', 3, $sucPrincipal],
        ['Usuario Demo Valencia', 'ventas.val', 2, $sucVal],
        ['Usuario Demo Maracay', 'almacen.mcy', 3, $sucMcy],
    ] as [$n, $u, $r, $s]) {
        DB::insert('usuarios', ['nombre' => $n, 'usuario' => $u, 'email' => "$u@demo.local", 'password_hash' => $hash, 'rol_id' => $r, 'sucursal_id' => $s]);
    }

    // Productos: [código, nombre, tipo, marca, modelo, desde, hasta, precio, costo, mínimo]
    $productos = [
        ['PB-TOY-COR-14', 'Parabrisas Toyota Corolla', 1, 'Toyota', 'Corolla', 2014, 2019, 185, 120, 4],
        ['PB-TOY-HLX-16', 'Parabrisas Toyota Hilux', 1, 'Toyota', 'Hilux', 2016, 2024, 230, 150, 4],
        ['PB-TOY-FTN-09', 'Parabrisas Toyota Fortuner', 1, 'Toyota', 'Fortuner', 2009, 2015, 240, 160, 3],
        ['PB-CHE-AVE-06', 'Parabrisas Chevrolet Aveo', 1, 'Chevrolet', 'Aveo', 2006, 2013, 120, 75, 5],
        ['PB-CHE-SPK-05', 'Parabrisas Chevrolet Spark', 1, 'Chevrolet', 'Spark', 2005, 2015, 110, 68, 5],
        ['PB-CHE-OPT-04', 'Parabrisas Chevrolet Optra', 1, 'Chevrolet', 'Optra', 2004, 2012, 130, 82, 4],
        ['PB-FOR-FIE-11', 'Parabrisas Ford Fiesta Power', 1, 'Ford', 'Fiesta', 2004, 2011, 125, 80, 4],
        ['PB-FOR-EXP-11', 'Parabrisas Ford Explorer', 1, 'Ford', 'Explorer', 2011, 2019, 290, 195, 2],
        ['PB-MIT-LAN-08', 'Parabrisas Mitsubishi Lancer', 1, 'Mitsubishi', 'Lancer', 2008, 2016, 160, 105, 3],
        ['PB-HYU-ACC-06', 'Parabrisas Hyundai Accent', 1, 'Hyundai', 'Accent', 2006, 2011, 135, 85, 3],
        ['PB-KIA-RIO-12', 'Parabrisas Kia Rio', 1, 'Kia', 'Rio', 2012, 2017, 140, 90, 3],
        ['PB-REN-LOG-07', 'Parabrisas Renault Logan', 1, 'Renault', 'Logan', 2007, 2014, 118, 74, 3],
        ['VP-TOY-COR-DI', 'Vidrio puerta delantera izquierda Toyota Corolla', 2, 'Toyota', 'Corolla', 2014, 2019, 65, 38, 3],
        ['VP-CHE-AVE-DD', 'Vidrio puerta delantera derecha Chevrolet Aveo', 2, 'Chevrolet', 'Aveo', 2006, 2013, 48, 27, 3],
        ['VP-FOR-FIE-TI', 'Vidrio puerta trasera izquierda Ford Fiesta', 2, 'Ford', 'Fiesta', 2004, 2011, 45, 25, 2],
        ['LU-TOY-HLX-16', 'Luneta térmica Toyota Hilux', 3, 'Toyota', 'Hilux', 2016, 2024, 95, 58, 2],
        ['LU-CHE-AVE-06', 'Luneta térmica Chevrolet Aveo sedán', 3, 'Chevrolet', 'Aveo', 2006, 2013, 85, 50, 2],
        ['LU-HYU-ACC-06', 'Luneta Hyundai Accent', 3, 'Hyundai', 'Accent', 2006, 2011, 80, 48, 2],
        ['AL-TOY-COR-14', 'Aleta trasera derecha Toyota Corolla', 4, 'Toyota', 'Corolla', 2014, 2019, 35, 18, 2],
        ['RV-UNI-CON-01', 'Espejo retrovisor interior universal', 5, null, null, null, null, 15, 7, 6],
        ['RV-CHE-SPK-IZ', 'Retrovisor lateral izquierdo Chevrolet Spark', 5, 'Chevrolet', 'Spark', 2005, 2015, 42, 24, 2],
        ['AC-URE-310', 'Sellador de uretano 310 ml', 6, null, null, null, null, 12, 6, 10],
        ['AC-MOL-UNI', 'Moldura de goma para parabrisas (metro)', 6, null, null, null, null, 4, 1.8, 20],
        ['AC-SEN-LLU', 'Soporte de sensor de lluvia', 6, null, null, null, null, 18, 9, 4],
    ];
    $ids = [];
    foreach ($productos as [$c, $n, $t, $ma, $mo, $d, $h, $pr, $co, $mi]) {
        $ids[$c] = DB::insert('productos', [
            'codigo' => $c, 'nombre' => $n . ($d ? " $d-$h" : ''), 'tipo_id' => $t, 'marca_vehiculo' => $ma, 'modelo_vehiculo' => $mo,
            'anio_desde' => $d, 'anio_hasta' => $h, 'precio' => $pr, 'costo' => $co, 'stock_minimo' => $mi,
            'descripcion' => $t === 1 ? 'Vidrio laminado de seguridad, con banda de sombra. Incluye moldura.' : null,
            'creado_en' => date('Y-m-d H:i:s', strtotime('-13 months')),
        ]);
    }

    // Clientes
    $clientes = [];
    foreach ([
        ['V', '00000001', 'Cliente Demo 01', null], ['V', '00000002', 'Cliente Demo 02', null],
        ['J', '00000003', 'Empresa Demo 01', null], ['V', '00000004', 'Cliente Demo 03', null],
        ['J', '00000005', 'Empresa Demo 02', null], ['V', '00000006', 'Cliente Demo 04', null],
        ['E', '00000007', 'Cliente Demo 05', null], ['J', '00000008', 'Empresa Demo 03', null],
        ['V', '00000009', 'Cliente Demo 06', null], ['V', '00000010', 'Cliente Demo 07', null],
        ['J', '00000011', 'Empresa Demo 04', null], ['V', '00000012', 'Cliente Demo 08', null],
    ] as [$td, $doc, $n, $tel]) {
        $clientes[] = DB::insert('clientes', ['tipo_documento' => $td, 'documento' => $doc, 'nombre' => $n, 'telefono' => $tel,
            'direccion' => 'Venezuela', 'creado_en' => date('Y-m-d H:i:s', strtotime('-13 months'))]);
    }

    // Popularidad relativa de cada producto
    $peso = [];
    foreach (array_keys($ids) as $i => $c) $peso[$c] = str_starts_with($c, 'PB-') ? 6 - ($i % 4) : (str_starts_with($c, 'AC-') ? 5 : 2);
    $elegir = function () use ($peso) {
        $t = mt_rand(1, array_sum($peso));
        foreach ($peso as $c => $w) { $t -= $w; if ($t <= 0) return $c; }
        return array_key_first($peso);
    };
    $metodos = Venta::METODOS_PAGO;

    // 12 meses de operaciones: compras al inicio del mes y ventas durante el mes
    for ($m = 11; $m >= 0; $m--) {
        $ini = strtotime(date('Y-m-01', strtotime("-$m months")));
        $fin = $m === 0 ? $ahora - 3600 : strtotime(date('Y-m-t 18:00:00', $ini));
        $marca = fn() => [
            (int)DB::value('SELECT COALESCE(MAX(id),0) FROM movimientos'), (int)DB::value('SELECT COALESCE(MAX(id),0) FROM pedidos'),
            (int)DB::value('SELECT COALESCE(MAX(id),0) FROM ventas'), (int)DB::value('SELECT COALESCE(MAX(id),0) FROM pedido_historial'),
            (int)DB::value('SELECT COALESCE(MAX(id),0) FROM bitacora'),
        ];

        // Reposición de inventario (fecha: día 1-3 del mes)
        $antes = $marca();
        DB::transaction(function () use ($ids, $sucursales, $m) {
            foreach ($ids as $c => $pid) {
                $min = (int)DB::value('SELECT stock_minimo FROM productos WHERE id = ?', [$pid]);
                foreach ($sucursales as $k => $s) {
                    // Reposición hasta un nivel objetivo (la principal mantiene más inventario)
                    $objetivo = $min + ($k === 0 ? mt_rand(3, 6) : mt_rand(1, 3)) * (str_starts_with($c, 'AC-') ? 3 : 1);
                    $cant = $objetivo - InventarioService::existencia($pid, $s);
                    if ($m === 0 && mt_rand(1, 3) === 1) $cant = 0; // este mes no llegó todo: genera alertas de stock
                    if ($cant > 0) InventarioService::mover($pid, $s, $cant, 'ENTRADA', 'Compra a proveedor (demo)');
                }
            }
        });
        fechar($antes, $ini + 3600 * 9, $ini + 86400 * 2);

        // Ventas del mes (crece con el tiempo), en orden cronológico
        $nVentas = 18 + (11 - $m) * 3 + mt_rand(0, 6);
        $tiempos = [];
        for ($v = 0; $v < $nVentas; $v++) $tiempos[] = mt_rand($ini + 86400 * 3, max($ini + 86400 * 3, $fin));
        sort($tiempos);
        for ($v = 0; $v < $nVentas; $v++) {
            $antes = $marca();
            $s = $sucursales[mt_rand(0, 9) < 5 ? 0 : mt_rand(1, 2)];
            $items = [];
            for ($k = 0, $n = mt_rand(1, 3); $k < $n; $k++) {
                $items[] = ['producto_id' => $ids[$elegir()], 'cantidad' => mt_rand(1, 10) > 8 ? 2 : 1];
            }
            try {
                $pid = PedidoService::crear($clientes[array_rand($clientes)], $s, $items, '');
                if (mt_rand(1, 10) > 3) PedidoService::cambiarEstado($pid, 'CONFIRMADO', 'Cliente confirmó');
                $desc = mt_rand(1, 10) > 8 ? 5.0 : 0.0;
                if ($m === 0 && $v >= $nVentas - 4) {
                    // Los últimos pedidos del mes quedan abiertos o cancelados
                    if ($v === $nVentas - 1) PedidoService::cambiarEstado($pid, 'CANCELADO', 'Cliente desistió');
                } else {
                    $vid = VentaService::registrar($pid, $metodos[array_rand($metodos)], $desc);
                    if ($m === 1 && $v === 3) VentaService::anular($vid, 'Error en el modelo del vehículo (demo)');
                }
            } catch (AppException) {
                // Sin stock suficiente: el pedido no se crea (comportamiento esperado)
            }
            $t = $tiempos[$v];
            fechar($antes, $t, $t + mt_rand(600, 7200));
        }
    }
}

/** Ajusta las fechas de los registros creados después de la marca $antes a un rango dado. */
function fechar(array $antes, int $desde, int $hasta): void
{
    [$mov, $ped, $ven, $his, $bit] = $antes;
    $base = date('Y-m-d H:i:s', $desde);
    $cierre = date('Y-m-d H:i:s', min($hasta, $desde + 1800));
    DB::run('UPDATE movimientos SET fecha = ? WHERE id > ?', [$cierre, $mov]);
    DB::run('UPDATE pedidos SET fecha = ? WHERE id > ?', [$base, $ped]);
    DB::run('UPDATE ventas SET fecha = ? WHERE id > ?', [$cierre, $ven]);
    DB::run('UPDATE pedido_historial SET fecha = ? WHERE id > ?', [$base, $his]);
    DB::run('UPDATE bitacora SET fecha = ? WHERE id > ?', [$base, $bit]);
}
