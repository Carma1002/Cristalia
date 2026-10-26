# Cristalia

**Sistema de gestión de inventario, pedidos y ventas para Corporación Parabrisas Venezuela, C.A.**
Proyecto de trabajo de grado. Aplicación web en PHP + MySQL con arquitectura de tres capas.

> *Cristalia*: gestión clara como el cristal.

---

## Funciones

| Módulo | Qué hace |
|---|---|
| **Seguridad y accesos** | Inicio/cierre de sesión, contraseñas cifradas (bcrypt), bloqueo tras 5 intentos fallidos, cierre por inactividad, protección CSRF. Usuarios, roles y permisos editables (Administrador, Ventas/Facturación, Almacén). Bitácora de operaciones críticas. |
| **Inventario** | Productos (código, tipo, vehículo, años, precio, costo, imagen, stock mínimo). Entradas, salidas y ajustes por conteo físico. Existencias por sucursal. Kardex completo. Alertas de stock mínimo. |
| **Catálogo digital** | Búsqueda y filtros por código, tipo, marca, modelo y año. Imagen, precio y disponibilidad por sucursal. |
| **Clientes, pedidos y ventas** | Clientes (V/E/J/G/P). Pedidos con verificación de existencias y **reserva** de unidades. Estados: PENDIENTE → CONFIRMADO → COMPLETADO / CANCELADO, con historial. Venta desde el pedido con **descuento automático del inventario** en una transacción. Anulación que devuelve el inventario. Comprobante interno imprimible y en PDF. |
| **Reportes e indicadores** | Panel con ventas del día y del mes, pedidos abiertos, alertas, valor del inventario, gráfico de 12 meses, más vendidos y rotación. Reportes de ventas (por día, mes, sucursal, vendedor o método de pago), por producto, inventario valorizado, rotación y movimientos. **Exportación a PDF y Excel.** |
| **Administración** | Sucursales, usuarios asignados a sucursal, datos de la empresa, tipos de producto, **copias de seguridad y restauración** desde la propia aplicación. |

### Reglas de negocio garantizadas
- Ninguna existencia puede quedar negativa (validación en código + restricción `CHECK` en la base de datos).
- Todo cambio de existencia queda en el kardex (`movimientos`) con saldo anterior y resultante.
- Un pedido abierto reserva sus unidades: no pueden venderse ni sacarse en otra operación.
- Las ventas, descuentos de inventario y cambios de estado se hacen en transacciones con bloqueo de filas (`SELECT … FOR UPDATE`).
- Los usuarios de Ventas y Almacén solo operan en su sucursal; el Administrador ve todas.
- Los comprobantes son **administrativos internos**, no facturas fiscales (fuera del alcance de la tesis).

---

## Tecnología

- **Backend:** PHP 8.1+ (sin frameworks ni Composer, para que funcione en hosting gratuito)
- **Base de datos:** MySQL / MariaDB (InnoDB, utf8mb4)
- **Interfaz:** HTML5, CSS3, JavaScript, Bootstrap 5, Bootstrap Icons, Chart.js (incluidos localmente en `assets/vendor`, no dependen de internet)
- **PDF:** FPDF · **Excel:** SimpleXLSXGen (incluidas en `app/lib`)

> La tesis menciona Laravel. Se usó PHP con una estructura MVC propia porque el hosting gratuito (InfinityFree) no ofrece consola ni Composer, y Laravel exige ambos. La arquitectura sigue siendo de tres capas: presentación (`views`), lógica de negocio (`controllers` + `services`) y datos (`models` + MySQL).

### Estructura

```
index.php              Controlador frontal (todas las rutas: index.php?r=modulo/accion)
install.php            Instalador web (se bloquea solo después de instalar)
app/
  bootstrap.php        Arranque: sesión, configuración, autoload
  routes.php           Rutas y permiso requerido por cada una
  core/                DB (PDO), Auth, Bitácora, Vista, PDF, Exportador, utilidades
  models/              Capa de datos: consultas SQL
  services/            Reglas de negocio: Inventario, Pedidos, Ventas, Respaldos
  controllers/         Capa de control (HTTP)
  views/               Capa de presentación (plantillas)
  lib/                 FPDF y SimpleXLSXGen
  storage/backups/     Respaldos generados (protegido)
assets/                CSS, JS, logo y librerías de interfaz
database/
  schema.sql           Tablas
  seed.sql             Roles, permisos, tipos de producto
  demo.php             Datos de demostración opcionales
uploads/productos/     Imágenes de productos
```

---

## 1) Subir a GitHub (desde el navegador, sin instalar nada)

El proyecto se entrega en paquetes ZIP. `Cristalia_completo.zip` trae todo (ideal para localhost). Para GitHub se usan estos, uno por carpeta:

| Paquete | Al descomprimirlo (clic derecho → Extraer todo) |
|---|---|
| `raiz.zip` | 6 archivos sueltos: `index.php`, `install.php`, `_verificar.php`, `README.md`, `.htaccess`, `.gitignore` |
| `app.zip` | carpeta `app/` (código del sistema) |
| `assets.zip` | carpeta `assets/` (estilos, scripts, librerías de interfaz) |
| `database.zip` | carpeta `database/` (tablas y datos de demostración) |
| `uploads.zip` | carpeta `uploads/` (imágenes de productos) |

1. Entrar a <https://github.com> → **+** → **New repository** → nombre `cristalia` → **Create repository** (no marcar "Add a README").
2. Descomprimir los ZIP. GitHub no descomprime los ZIP solo.
3. En el repositorio, pulsar **uploading an existing file** (o **Add file → Upload files**).
4. Arrastrar, haciendo **Commit changes** después de cada uno:
   1. Los **6 archivos** de `raiz.zip` (sueltos).
   2. La **carpeta** `app`.
   3. La **carpeta** `assets`.
   4. La **carpeta** `database`.
   5. La **carpeta** `uploads`.
5. Verificar que en la página principal del repositorio se ven **directamente** `app`, `assets`, `database`, `uploads`, `index.php` e `install.php`. Si una carpeta quedó dentro de otra (por ejemplo `app/app`), está mal subida.

> - Usar Chrome o Edge: al **arrastrar una carpeta**, GitHub conserva sus subcarpetas.
> - GitHub acepta hasta 100 archivos por subida. Por eso va carpeta por carpeta (la más grande tiene menos de 100).
> - El cargador web de GitHub puede rechazar archivos que empiezan con punto con el aviso “This file is hidden”. Créelos desde **Add file → Create new file**, con su ruta exacta, y copie el contenido del archivo original. Esto aplica a `.gitignore`, `.htaccess`, `app/.htaccess`, `database/.htaccess` y `uploads/.htaccess`; `uploads/productos/.gitkeep` es opcional.
> - **Nunca** subir `app/config.php` (lo crea el instalador y contiene la contraseña de la base de datos).

## 2) Probar en la computadora (localhost con XAMPP)

1. Instalar **XAMPP** (gratis) desde <https://www.apachefriends.org> con PHP 8.1 o superior.
2. Abrir el **XAMPP Control Panel** y pulsar **Start** en **Apache** y en **MySQL**.
3. Descomprimir `Cristalia_completo.zip` (o en GitHub **Code → Download ZIP**) y buscar la carpeta que tiene `index.php`, `app` y `assets` juntos.
4. Copiarla a `C:\xampp\htdocs\` con el nombre `cristalia`: debe quedar `C:\xampp\htdocs\cristalia\index.php` y `C:\xampp\htdocs\cristalia\app\` (en Mac: `/Applications/XAMPP/htdocs/`).
5. En el navegador abrir **http://localhost/cristalia/install.php** y completar:
   - Host `localhost` · Puerto `3306` · Base `cristalia` · Usuario `root` · Contraseña **vacía** (la base se crea sola).
   - Empresa, sucursal y el usuario administrador.
   - Marcar **Cargar datos de demostración** para tener 3 sucursales, 24 productos y 12 meses de ventas.
6. Entrar en **http://localhost/cristalia/** con el administrador.

> Para reinstalar desde cero: borrar `app/config.php` y volver a abrir `install.php`.

## 3) Publicar gratis en InfinityFree (subdominio)

1. Crear cuenta en <https://infinityfree.com> → **Create Account** → elegir un **subdominio gratuito** (ej. `cristalia.infinityfreeapp.com`) o conectar un dominio propio.
2. Panel de control → **MySQL Databases** → crear una base (ej. `cristalia`). Anotar: **host** (ej. `sql123.infinityfree.com`), **nombre** (`if0_XXXX_cristalia`), **usuario** (`if0_XXXX`) y **contraseña** (la de la cuenta de hosting, visible en *Account → Password*).
3. En GitHub: **Code → Download ZIP** y descomprimirlo.
4. Panel → **Online File Manager** → carpeta `htdocs` → borrar los archivos de ejemplo → subir el **contenido** de la carpeta descargada (que `index.php` quede directamente dentro de `htdocs`). También se puede con FileZilla usando los datos FTP del panel.
5. Abrir `https://SU-SUBDOMINIO/install.php` y completar los datos de MySQL de InfinityFree.
6. Listo. El sistema queda accesible desde cualquier lugar.

> InfinityFree puede tardar unos minutos en activar el subdominio y el certificado SSL. Si el instalador no puede escribir `app/config.php`, muestra el contenido para crearlo a mano desde el administrador de archivos.

## Usuarios de demostración
(Solo si se marcó la casilla de datos de demostración. El instalador genera una contraseña temporal aleatoria y la muestra al finalizar.)

| Usuario | Rol | Sucursal |
|---|---|---|
| `ventas` | Ventas/Facturación | Caracas |
| `almacen` | Almacén | Caracas |
| `ventas.val` | Ventas/Facturación | Valencia |
| `almacen.mcy` | Almacén | Maracay |

El administrador es el que se crea en el instalador.

## Respaldos
*Administración → Respaldos*: genera un archivo `.sql` completo, permite descargarlo y restaurarlo (desde el servidor o subiendo el archivo). Antes de restaurar se crea una copia automática. El hosting gratuito no garantiza conservar archivos: descargue los respaldos a otro equipo.

## Licencias de terceros
Bootstrap y Bootstrap Icons (MIT), Chart.js (MIT), FPDF (licencia libre), SimpleXLSXGen (MIT). Sus licencias están junto a cada librería.
