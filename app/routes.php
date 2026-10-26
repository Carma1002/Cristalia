<?php
/**
 * Tabla de rutas: 'ruta' => [Controlador, método, permiso requerido]
 *  - 'public' = sin sesión
 *  - 'auth'   = cualquier usuario autenticado
 *  - otro     = clave de permiso (ver tabla `permisos`)
 * Las acciones que modifican datos sólo aceptan POST y validan CSRF.
 */
return [
    // Seguridad
    'login'                  => ['AuthController', 'login', 'public'],
    'logout'                 => ['AuthController', 'logout', 'auth'],
    'perfil'                 => ['AuthController', 'perfil', 'auth'],

    // Panel
    'dashboard'              => ['DashboardController', 'index', 'auth'],

    // Inventario
    'productos'              => ['ProductoController', 'index', 'inventario.ver'],
    'productos/ver'          => ['ProductoController', 'ver', 'inventario.ver'],
    'productos/crear'        => ['ProductoController', 'form', 'inventario.gestionar'],
    'productos/editar'       => ['ProductoController', 'form', 'inventario.gestionar'],
    'productos/guardar'      => ['ProductoController', 'guardar', 'inventario.gestionar'],
    'productos/estado'       => ['ProductoController', 'estado', 'inventario.gestionar'],
    'existencias'            => ['InventarioController', 'existencias', 'inventario.ver'],
    'stock-bajo'             => ['InventarioController', 'stockBajo', 'inventario.ver'],
    'movimientos'            => ['InventarioController', 'movimientos', 'inventario.ver'],
    'movimientos/nuevo'      => ['InventarioController', 'form', 'inventario.movimientos'],
    'movimientos/guardar'    => ['InventarioController', 'guardar', 'inventario.movimientos'],

    // Catálogo
    'catalogo'               => ['CatalogoController', 'index', 'catalogo.ver'],

    // Clientes
    'clientes'               => ['ClienteController', 'index', 'clientes.ver'],
    'clientes/ver'           => ['ClienteController', 'ver', 'clientes.ver'],
    'clientes/crear'         => ['ClienteController', 'form', 'clientes.gestionar'],
    'clientes/editar'        => ['ClienteController', 'form', 'clientes.gestionar'],
    'clientes/guardar'       => ['ClienteController', 'guardar', 'clientes.gestionar'],
    'clientes/buscar'        => ['ClienteController', 'buscarJson', 'pedidos.gestionar'],

    // Pedidos
    'pedidos'                => ['PedidoController', 'index', 'pedidos.ver'],
    'pedidos/ver'            => ['PedidoController', 'ver', 'pedidos.ver'],
    'pedidos/crear'          => ['PedidoController', 'form', 'pedidos.gestionar'],
    'pedidos/guardar'        => ['PedidoController', 'guardar', 'pedidos.gestionar'],
    'pedidos/estado'         => ['PedidoController', 'estado', 'pedidos.gestionar'],
    'api/productos'          => ['InventarioController', 'buscarJson', 'auth'],

    // Ventas
    'ventas'                 => ['VentaController', 'index', 'ventas.ver'],
    'ventas/ver'             => ['VentaController', 'ver', 'ventas.ver'],
    'ventas/registrar'       => ['VentaController', 'form', 'ventas.registrar'],
    'ventas/guardar'         => ['VentaController', 'guardar', 'ventas.registrar'],
    'ventas/anular'          => ['VentaController', 'anular', 'ventas.anular'],
    'ventas/comprobante'     => ['VentaController', 'comprobante', 'ventas.ver'],
    'ventas/comprobante-pdf' => ['VentaController', 'comprobantePdf', 'ventas.ver'],

    // Reportes
    'reportes'               => ['ReporteController', 'index', 'reportes.ver'],
    'reportes/inventario'    => ['ReporteController', 'inventario', 'reportes.ver'],
    'reportes/ventas'        => ['ReporteController', 'ventas', 'reportes.ver'],
    'reportes/productos'     => ['ReporteController', 'productos', 'reportes.ver'],
    'reportes/movimientos'   => ['ReporteController', 'movimientos', 'reportes.ver'],
    'reportes/rotacion'      => ['ReporteController', 'rotacion', 'reportes.ver'],

    // Administración
    'usuarios'               => ['UsuarioController', 'index', 'admin.usuarios'],
    'usuarios/crear'         => ['UsuarioController', 'form', 'admin.usuarios'],
    'usuarios/editar'        => ['UsuarioController', 'form', 'admin.usuarios'],
    'usuarios/guardar'       => ['UsuarioController', 'guardar', 'admin.usuarios'],
    'usuarios/estado'        => ['UsuarioController', 'estado', 'admin.usuarios'],
    'roles'                  => ['RolController', 'index', 'admin.roles'],
    'roles/editar'           => ['RolController', 'form', 'admin.roles'],
    'roles/crear'            => ['RolController', 'form', 'admin.roles'],
    'roles/guardar'          => ['RolController', 'guardar', 'admin.roles'],
    'roles/eliminar'         => ['RolController', 'eliminar', 'admin.roles'],
    'sucursales'             => ['SucursalController', 'index', 'admin.sucursales'],
    'sucursales/crear'       => ['SucursalController', 'form', 'admin.sucursales'],
    'sucursales/editar'      => ['SucursalController', 'form', 'admin.sucursales'],
    'sucursales/guardar'     => ['SucursalController', 'guardar', 'admin.sucursales'],
    'bitacora'               => ['AdminController', 'bitacora', 'admin.bitacora'],
    'respaldos'              => ['AdminController', 'respaldos', 'admin.respaldos'],
    'respaldos/generar'      => ['AdminController', 'generarRespaldo', 'admin.respaldos'],
    'respaldos/descargar'    => ['AdminController', 'descargarRespaldo', 'admin.respaldos'],
    'respaldos/eliminar'     => ['AdminController', 'eliminarRespaldo', 'admin.respaldos'],
    'respaldos/restaurar'    => ['AdminController', 'restaurar', 'admin.respaldos'],
    'configuracion'          => ['AdminController', 'configuracion', 'admin.configuracion'],
    'configuracion/guardar'  => ['AdminController', 'guardarConfiguracion', 'admin.configuracion'],
    'tipos/guardar'          => ['AdminController', 'guardarTipo', 'admin.configuracion'],
];
