# Cristalia

**Sistema de gestión de inventario, pedidos y ventas para una empresa de autopartes.**
Proyecto de trabajo de grado. Aplicación web en PHP + MySQL con arquitectura de tres capas.

> *Cristalia*: gestión clara como el cristal.

---

## Cómo está organizado

La interfaz tiene **5 secciones** en la barra superior (en el celular, en una barra inferior). Cada sección agrupa sus pantallas en pestañas:

| Sección | Pestañas | Qué cubre de la tesis |
|---|---|---|
| **Inicio** | — | Panel de indicadores: ventas del día y del mes, pedidos por completar, productos por reponer, ventas mensuales, ventas por sucursal, más vendidos y accesos rápidos. |
| **Inventario** | Productos · Entradas y salidas | Catálogo digital (vista de tarjetas con imagen, precio y disponibilidad), existencias por sucursal (vista de lista), alertas de stock mínimo (filtro *Por reponer*), entradas, salidas y ajustes por conteo con su kardex. |
| **Comercio** | Pedidos · Ventas · Clientes | Clientes; pedidos que verifican y **apartan** existencias, con estados (Pendiente → Confirmado → Completado / Cancelado); venta desde el pedido con **descuento automático del inventario**; anulación; comprobante interno imprimible y en PDF. |
| **Reportes** | Ventas · Inventario | Ventas por día, mes, sucursal, vendedor o método de pago y productos más vendidos; inventario valorizado con estado de stock y **rotación**. Todo se exporta a **PDF y Excel**. |
| **Administración** | Usuarios y permisos · Empresa y sucursales · Respaldos · Bitácora | Usuarios asignados a sucursal; permisos de los 3 roles (Administrador, Ventas/Facturación, Almacén) en una tabla de casillas; datos y logo de la empresa; sucursales; tipos de producto; copias de seguridad y restauración; bitácora de operaciones. |

Cada usuario sólo ve las secciones que su rol le permite.

### Identidad visual
Cristalia usa su manual de marca: Azul Cristalia `#0F536B`, Azul Profundo `#092E3D`, Cian Faceta `#3FA7BF`, Hielo, Bruma, Pizarra y Grafito; tipografías **Poppins** (títulos y cifras) e **Inter** (textos), incluidas en `assets/vendor/fonts`. Los logotipos están en `assets/img` como SVG.

La empresa que usa el sistema (el cliente) aparece con **su propio logo** en el inicio de sesión, el menú de usuario, los comprobantes y los PDF. Se cambia en *Administración → Empresa y sucursales*.

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
  nav.php              Menú: 5 secciones y sus pestañas
  core/                DB (PDO), Auth, Bitácora, Vista, PDF, Exportador, utilidades
  models/              Capa de datos: consultas SQL
  services/            Reglas de negocio: Inventario, Pedidos, Ventas, Respaldos
  controllers/         Capa de control (HTTP)
  views/               Capa de presentación (plantillas)
  lib/                 FPDF y SimpleXLSXGen
  storage/backups/     Respaldos generados (protegido)
assets/                Estilos, interacciones, logotipos, fuentes y librerías de interfaz
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

## Actualizar una instalación existente (InfinityFree o localhost)

La versión 2.0 **no cambia la base de datos**: basta con reemplazar los archivos. Los datos, usuarios y `app/config.php` se conservan.

1. Descargue el proyecto desde GitHub (**Code → Download ZIP**).
2. Suba y reemplace en el servidor las carpetas `app`, `assets`, `database` y los archivos `index.php`, `install.php` y `_verificar.php`. **No borre** `app/config.php` ni la carpeta `uploads` (allí están las fotos de los productos).
3. Opcional: puede borrar estos archivos que ya no se usan (si quedan, no afectan): `app/controllers/CatalogoController.php`, `app/views/catalogo/`, `app/views/inventario/existencias.php`, `app/views/inventario/stock_bajo.php`, `app/views/dashboard/simple.php`, `app/views/reportes/index.php`, `app/views/reportes/productos.php`, `app/views/reportes/rotacion.php`, `app/views/reportes/movimientos.php`, `app/views/reportes/_barra.php`, `app/views/admin/roles.php`, `app/views/admin/rol_form.php`, `app/views/admin/sucursales.php`, `app/views/admin/sucursal_form.php`, `app/views/admin/configuracion.php`, `assets/img/logo.svg`.
4. Si el navegador muestra el estilo anterior, recargue con `Ctrl + F5`.

## Usuarios de demostración
(Solo si se marcó la casilla de datos de demostración. El instalador genera una contraseña temporal aleatoria y la muestra al finalizar.)

| Usuario | Rol | Sucursal |
|---|---|---|
| `ventas` | Ventas/Facturación | Sucursal principal |
| `almacen` | Almacén | Sucursal principal |
| `ventas.val` | Ventas/Facturación | Sucursal Demo 2 |
| `almacen.mcy` | Almacén | Sucursal Demo 3 |

El administrador es el que se crea en el instalador.

## Respaldos
*Administración → Respaldos*: genera un archivo `.sql` completo, permite descargarlo y restaurarlo (desde el servidor o subiendo el archivo). Antes de restaurar se crea una copia automática. El hosting gratuito no garantiza conservar archivos: descargue los respaldos a otro equipo.

## Licencias de terceros
Bootstrap y Bootstrap Icons (MIT), Chart.js (MIT), FPDF (licencia libre), SimpleXLSXGen (MIT), fuentes Poppins, Inter y Montserrat (SIL Open Font License). Sus licencias están junto a cada librería.
