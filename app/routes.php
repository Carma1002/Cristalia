<?php
/**
 * Tabla de rutas: 'ruta' => [Controlador, método, permiso requerido]
 *  - 'public' = sin sesión
 *  - 'auth'   = cualquier usuario autenticado
 *  - otro     = clave de permiso (ver tabla `permisos`); un arreglo = basta con uno de ellos
 * Las acciones que modifican datos sólo aceptan POST y validan CSRF.
 */
return [
    // Seguridad
    'login'                  => ['AuthController', 'login', 'public'],
    'logout'                 => ['AuthController', 'logout', 'auth'],
    'perfil'                 => ['AuthController', 'perfil', 'auth'],

    // Inicio
    'dashboard'              => ['DashboardController', 'index', 'auth'],

    // Inventario (productos = catálogo + existencias + alertas de stock)
    'productos'              => ['ProductoController', 'index', ['inventario.ver', 'catalogo.ver']],
    'productos/ver'          => ['ProductoController', 'ver', ['inventario.ver', 'catalogo.ver']],
    'productos/crear'        => ['ProductoController', 'form', 'inventario.gestionar'],
    'productos/editar'       => ['ProductoController', 'form', 'inventario.gestionar'],
    'productos/guardar'      => ['ProductoController', 'guardar', 'inventario.gestionar'],
    'productos/estado'       => ['ProductoController', 'estado', 'inventario.gestionar'],
    'movimientos'            => ['InventarioController', 'movimientos', 'inventario.ver'],
    'movimientos/nuevo'      => ['InventarioController', 'form', 'inventario.movimientos'],
    'movimientos/guardar'    => ['InventarioController', 'guardar', 'inventario.movimientos'],
    'api/productos'          => ['InventarioController', 'buscarJson', 'auth'],

    // Comercio
    'clientes'               => ['ClienteController', 'index', 'clientes.ver'],
    'clientes/ver'           => ['ClienteController', 'ver', 'clientes.ver'],
    'clientes/crear'         => ['ClienteController', 'form', 'clientes.gestionar'],
    'clientes/editar'        => ['ClienteController', 'form', 'clientes.gestionar'],
    'clientes/guardar'       => ['ClienteController', 'guardar', 'clientes.gestionar'],
    'clientes/buscar'        => ['ClienteController', 'buscarJson', ['clientes.ver', 'pedidos.gestionar']],
    'pedidos'                => ['PedidoController', 'index', 'pedidos.ver'],
    'pedidos/ver'            => ['PedidoController', 'ver', 'pedidos.ver'],
    'pedidos/crear'          => ['PedidoController', 'form', 'pedidos.gestionar'],
    'pedidos/guardar'        => ['PedidoController', 'guardar', 'pedidos.gestionar'],
    'pedidos/estado'         => ['PedidoController', 'estado', 'pedidos.gestionar'],
    'ventas'                 => ['VentaController', 'index', 'ventas.ver'],
    'ventas/ver'             => ['VentaController', 'ver', 'ventas.ver'],
    'ventas/registrar'       => ['VentaController', 'form', 'ventas.registrar'],
    'ventas/guardar'         => ['VentaController', 'guardar', 'ventas.registrar'],
    'ventas/anular'          => ['VentaController', 'anular', 'ventas.anular'],
    'ventas/comprobante'     => ['VentaController', 'comprobante', 'ventas.ver'],
    'ventas/comprobante-pdf' => ['VentaController', 'comprobantePdf', 'ventas.ver'],

    // Reportes (2 pantallas: ventas e inventario)
    'reportes'               => ['ReporteController', 'ventas', 'reportes.ver'],
    'reportes/ventas'        => ['ReporteController', 'ventas', 'reportes.ver'],
    'reportes/inventario'    => ['ReporteController', 'inventario', 'reportes.ver'],

    // Administración
    'usuarios'               => ['UsuarioController', 'index', ['admin.usuarios', 'admin.roles']],
    'usuarios/crear'         => ['UsuarioController', 'form', 'admin.usuarios'],
    'usuarios/editar'        => ['UsuarioController', 'form', 'admin.usuarios'],
    'usuarios/guardar'       => ['UsuarioController', 'guardar', 'admin.usuarios'],
    'usuarios/estado'        => ['UsuarioController', 'estado', 'admin.usuarios'],
    'roles/guardar'          => ['RolController', 'guardar', 'admin.roles'],
    'empresa'                => ['AdminController', 'empresa', ['admin.configuracion', 'admin.sucursales']],
    'configuracion/guardar'  => ['AdminController', 'guardarConfiguracion', 'admin.configuracion'],
    'tipos/guardar'          => ['AdminController', 'guardarTipo', 'admin.configuracion'],
    'sucursales/guardar'     => ['SucursalController', 'guardar', 'admin.sucursales'],
    'respaldos'              => ['AdminController', 'respaldos', 'admin.respaldos'],
    'respaldos/generar'      => ['AdminController', 'generarRespaldo', 'admin.respaldos'],
    'respaldos/descargar'    => ['AdminController', 'descargarRespaldo', 'admin.respaldos'],
    'respaldos/eliminar'     => ['AdminController', 'eliminarRespaldo', 'admin.respaldos'],
    'respaldos/restaurar'    => ['AdminController', 'restaurar', 'admin.respaldos'],
    'bitacora'               => ['AdminController', 'bitacora', 'admin.bitacora'],
];
