-- =====================================================================
-- Cristalia · Datos base (roles, permisos, tipos de producto)
-- El usuario administrador y la primera sucursal los crea el instalador.
-- =====================================================================

INSERT INTO configuracion (clave, valor) VALUES
('empresa_nombre', 'Corporación Parabrisas Venezuela, C.A.'),
('empresa_rif', 'J-00000000-0'),
('empresa_direccion', ''),
('moneda', '$'),
('empresa_logo', ''),
('version_bd', '1');

INSERT INTO roles (id, nombre, descripcion, es_sistema) VALUES
(1, 'Administrador', 'Acceso total al sistema', 1),
(2, 'Ventas/Facturación', 'Clientes, pedidos, ventas y comprobantes', 0),
(3, 'Almacén', 'Productos, existencias y movimientos de inventario', 0);

INSERT INTO permisos (id, clave, modulo, descripcion) VALUES
(1,  'dashboard.ver',          'Panel',       'Ver panel de indicadores'),
(2,  'catalogo.ver',           'Catálogo',    'Consultar catálogo digital'),
(3,  'inventario.ver',         'Inventario',  'Consultar productos y existencias'),
(4,  'inventario.gestionar',   'Inventario',  'Crear y editar productos'),
(5,  'inventario.movimientos', 'Inventario',  'Registrar entradas, salidas y ajustes'),
(6,  'clientes.ver',           'Clientes',    'Consultar clientes'),
(7,  'clientes.gestionar',     'Clientes',    'Crear y editar clientes'),
(8,  'pedidos.ver',            'Pedidos',     'Consultar pedidos'),
(9,  'pedidos.gestionar',      'Pedidos',     'Crear pedidos y cambiar su estado'),
(10, 'ventas.ver',             'Ventas',      'Consultar ventas y comprobantes'),
(11, 'ventas.registrar',       'Ventas',      'Registrar ventas a partir de pedidos'),
(12, 'ventas.anular',          'Ventas',      'Anular ventas (devuelve el inventario)'),
(13, 'reportes.ver',           'Reportes',    'Consultar y exportar reportes'),
(14, 'admin.usuarios',         'Administración', 'Gestionar usuarios'),
(15, 'admin.roles',            'Administración', 'Gestionar roles y permisos'),
(16, 'admin.sucursales',       'Administración', 'Gestionar sucursales'),
(17, 'admin.respaldos',        'Administración', 'Copias de seguridad y restauración'),
(18, 'admin.bitacora',         'Administración', 'Consultar bitácora de operaciones'),
(19, 'admin.configuracion',    'Administración', 'Datos de la empresa y tipos de producto');

-- Administrador: todos los permisos
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 1, id FROM permisos;

-- Ventas/Facturación
INSERT INTO rol_permiso (rol_id, permiso_id) VALUES
(2,1),(2,2),(2,3),(2,6),(2,7),(2,8),(2,9),(2,10),(2,11),(2,13);

-- Almacén
INSERT INTO rol_permiso (rol_id, permiso_id) VALUES
(3,1),(3,2),(3,3),(3,4),(3,5),(3,8),(3,13);

INSERT INTO tipos_producto (id, nombre) VALUES
(1, 'Parabrisas'),
(2, 'Vidrio de puerta'),
(3, 'Luneta / Vidrio trasero'),
(4, 'Vidrio lateral fijo (aleta)'),
(5, 'Retrovisor / Espejo'),
(6, 'Accesorios e instalación');
