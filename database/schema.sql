-- =====================================================================
-- Cristalia · Sistema de gestión de inventario para empresas de autopartes
-- Esquema de base de datos (MySQL 5.7+ / MariaDB 10.3+)
-- Motor InnoDB (transacciones + claves foráneas), codificación utf8mb4
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS venta_detalle;
DROP TABLE IF EXISTS ventas;
DROP TABLE IF EXISTS pedido_historial;
DROP TABLE IF EXISTS pedido_detalle;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS movimientos;
DROP TABLE IF EXISTS existencias;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS tipos_producto;
DROP TABLE IF EXISTS bitacora;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS rol_permiso;
DROP TABLE IF EXISTS permisos;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS sucursales;
DROP TABLE IF EXISTS configuracion;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Administración
-- ---------------------------------------------------------------------
CREATE TABLE configuracion (
  clave VARCHAR(60) NOT NULL PRIMARY KEY,
  valor VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sucursales (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(10) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  direccion VARCHAR(255) NULL,
  telefono VARCHAR(30) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Seguridad y accesos
-- ---------------------------------------------------------------------
CREATE TABLE roles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE,
  descripcion VARCHAR(255) NULL,
  es_sistema TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE permisos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(60) NOT NULL UNIQUE,
  modulo VARCHAR(50) NOT NULL,
  descripcion VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rol_permiso (
  rol_id INT UNSIGNED NOT NULL,
  permiso_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (rol_id, permiso_id),
  CONSTRAINT fk_rp_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_rp_perm FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  usuario VARCHAR(40) NOT NULL UNIQUE,
  email VARCHAR(120) NULL,
  password_hash VARCHAR(255) NOT NULL,
  rol_id INT UNSIGNED NOT NULL,
  sucursal_id INT UNSIGNED NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  intentos_fallidos INT NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  ultimo_acceso DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usr_rol FOREIGN KEY (rol_id) REFERENCES roles(id),
  CONSTRAINT fk_usr_suc FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bitacora (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  modulo VARCHAR(50) NOT NULL,
  accion VARCHAR(60) NOT NULL,
  detalle TEXT NULL,
  ip VARCHAR(45) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bit_fecha (fecha),
  CONSTRAINT fk_bit_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Inventario y catálogo
-- ---------------------------------------------------------------------
CREATE TABLE tipos_producto (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE productos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(150) NOT NULL,
  tipo_id INT UNSIGNED NOT NULL,
  marca_vehiculo VARCHAR(60) NULL,
  modelo_vehiculo VARCHAR(80) NULL,
  anio_desde SMALLINT NULL,
  anio_hasta SMALLINT NULL,
  descripcion TEXT NULL,
  precio DECIMAL(12,2) NOT NULL DEFAULT 0,
  costo DECIMAL(12,2) NOT NULL DEFAULT 0,
  stock_minimo INT NOT NULL DEFAULT 0,
  imagen VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL,
  INDEX idx_prod_marca (marca_vehiculo),
  INDEX idx_prod_modelo (modelo_vehiculo),
  CONSTRAINT fk_prod_tipo FOREIGN KEY (tipo_id) REFERENCES tipos_producto(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Existencias por producto y sucursal
CREATE TABLE existencias (
  producto_id INT UNSIGNED NOT NULL,
  sucursal_id INT UNSIGNED NOT NULL,
  cantidad INT NOT NULL DEFAULT 0,
  actualizado_en DATETIME NULL,
  PRIMARY KEY (producto_id, sucursal_id),
  CONSTRAINT fk_ex_prod FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  CONSTRAINT fk_ex_suc FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
  CONSTRAINT chk_ex_cant CHECK (cantidad >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kardex: todo cambio de existencia queda registrado aquí
CREATE TABLE movimientos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  producto_id INT UNSIGNED NOT NULL,
  sucursal_id INT UNSIGNED NOT NULL,
  tipo VARCHAR(20) NOT NULL,          -- ENTRADA | SALIDA | AJUSTE | VENTA | ANULACION
  cantidad INT NOT NULL,               -- positiva = suma, negativa = resta
  stock_anterior INT NOT NULL,
  stock_resultante INT NOT NULL,
  motivo VARCHAR(255) NULL,
  referencia VARCHAR(60) NULL,
  usuario_id INT UNSIGNED NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_mov_fecha (fecha),
  CONSTRAINT fk_mov_prod FOREIGN KEY (producto_id) REFERENCES productos(id),
  CONSTRAINT fk_mov_suc FOREIGN KEY (sucursal_id) REFERENCES sucursales(id),
  CONSTRAINT fk_mov_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Clientes, pedidos y ventas
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tipo_documento VARCHAR(2) NOT NULL DEFAULT 'V',   -- V, E, J, G, P
  documento VARCHAR(20) NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  telefono VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  direccion VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cli_doc (tipo_documento, documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedidos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  numero VARCHAR(20) NULL UNIQUE,
  cliente_id INT UNSIGNED NOT NULL,
  sucursal_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE', -- PENDIENTE | CONFIRMADO | COMPLETADO | CANCELADO
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  observaciones VARCHAR(255) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ped_estado (estado),
  CONSTRAINT fk_ped_cli FOREIGN KEY (cliente_id) REFERENCES clientes(id),
  CONSTRAINT fk_ped_suc FOREIGN KEY (sucursal_id) REFERENCES sucursales(id),
  CONSTRAINT fk_ped_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedido_detalle (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT UNSIGNED NOT NULL,
  producto_id INT UNSIGNED NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_pd_ped FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  CONSTRAINT fk_pd_prod FOREIGN KEY (producto_id) REFERENCES productos(id),
  CONSTRAINT chk_pd_cant CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedido_historial (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT UNSIGNED NOT NULL,
  estado_anterior VARCHAR(20) NULL,
  estado_nuevo VARCHAR(20) NOT NULL,
  nota VARCHAR(255) NULL,
  usuario_id INT UNSIGNED NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ph_ped FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  CONSTRAINT fk_ph_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ventas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  numero VARCHAR(20) NULL UNIQUE,
  pedido_id INT UNSIGNED NOT NULL UNIQUE,
  cliente_id INT UNSIGNED NOT NULL,
  sucursal_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  descuento DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL,
  metodo_pago VARCHAR(30) NOT NULL,
  estado VARCHAR(15) NOT NULL DEFAULT 'ACTIVA',   -- ACTIVA | ANULADA
  motivo_anulacion VARCHAR(255) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ven_fecha (fecha),
  CONSTRAINT fk_ven_ped FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
  CONSTRAINT fk_ven_cli FOREIGN KEY (cliente_id) REFERENCES clientes(id),
  CONSTRAINT fk_ven_suc FOREIGN KEY (sucursal_id) REFERENCES sucursales(id),
  CONSTRAINT fk_ven_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE venta_detalle (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  venta_id INT UNSIGNED NOT NULL,
  producto_id INT UNSIGNED NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_vd_ven FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
  CONSTRAINT fk_vd_prod FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
