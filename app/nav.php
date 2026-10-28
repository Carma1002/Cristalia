<?php
/**
 * Mapa de navegación de Cristalia.
 * 5 secciones en la barra superior; cada sección agrupa sus pantallas en pestañas.
 *  - perm:  permiso requerido ('auth' = cualquier usuario; un arreglo = cualquiera de ellos)
 *  - match: rutas que pertenecen a la pestaña (se compara por prefijo "ruta" o "ruta/...")
 */
return [
    [
        'key' => 'inicio', 'label' => 'Inicio', 'icon' => 'house-door',
        'tabs' => [
            ['r' => 'dashboard', 'label' => 'Inicio', 'perm' => 'auth', 'match' => ['dashboard', 'perfil']],
        ],
    ],
    [
        'key' => 'inventario', 'label' => 'Inventario', 'icon' => 'box-seam',
        'tabs' => [
            ['r' => 'productos', 'label' => 'Productos', 'icon' => 'grid', 'perm' => ['inventario.ver', 'catalogo.ver'], 'match' => ['productos']],
            ['r' => 'movimientos', 'label' => 'Entradas y salidas', 'icon' => 'arrow-left-right', 'perm' => 'inventario.ver', 'match' => ['movimientos']],
        ],
    ],
    [
        'key' => 'comercio', 'label' => 'Comercio', 'icon' => 'bag',
        'tabs' => [
            ['r' => 'pedidos', 'label' => 'Pedidos', 'icon' => 'clipboard-check', 'perm' => 'pedidos.ver', 'match' => ['pedidos']],
            ['r' => 'ventas', 'label' => 'Ventas', 'icon' => 'receipt', 'perm' => 'ventas.ver', 'match' => ['ventas']],
            ['r' => 'clientes', 'label' => 'Clientes', 'icon' => 'people', 'perm' => 'clientes.ver', 'match' => ['clientes']],
        ],
    ],
    [
        'key' => 'reportes', 'label' => 'Reportes', 'icon' => 'bar-chart-line',
        'tabs' => [
            ['r' => 'reportes', 'label' => 'Ventas', 'icon' => 'graph-up', 'perm' => 'reportes.ver', 'match' => ['reportes', 'reportes/ventas']],
            ['r' => 'reportes/inventario', 'label' => 'Inventario', 'icon' => 'boxes', 'perm' => 'reportes.ver', 'match' => ['reportes/inventario']],
        ],
    ],
    [
        'key' => 'admin', 'label' => 'Administración', 'icon' => 'sliders',
        'tabs' => [
            ['r' => 'usuarios', 'label' => 'Usuarios y permisos', 'icon' => 'people', 'perm' => ['admin.usuarios', 'admin.roles'], 'match' => ['usuarios', 'roles']],
            ['r' => 'empresa', 'label' => 'Empresa y sucursales', 'icon' => 'building', 'perm' => ['admin.configuracion', 'admin.sucursales'], 'match' => ['empresa', 'sucursales', 'tipos', 'configuracion']],
            ['r' => 'respaldos', 'label' => 'Respaldos', 'icon' => 'cloud-arrow-down', 'perm' => 'admin.respaldos', 'match' => ['respaldos']],
            ['r' => 'bitacora', 'label' => 'Bitácora', 'icon' => 'journal-text', 'perm' => 'admin.bitacora', 'match' => ['bitacora']],
        ],
    ],
];
