# Sistema de Gestión de Biblioteca — Primer Parcial

Aplicación web en **PHP vanilla + MySQL** (sin frameworks) para la gestión de una
biblioteca: usuarios, perfiles y permisos, catálogo de libros, préstamos con
control de stock transaccional, auditoría, y reportes con gráficos exportables.

## Requisitos

- PHP 8.1 o superior, con las extensiones `pdo_mysql`, `openssl` y `mbstring` habilitadas (vienen por defecto en XAMPP/WAMP/Laragon).
- MySQL 5.7+ o MariaDB 10.4+.
- Un servidor web (Apache/Nginx) o alcanza con el servidor embebido de PHP para desarrollo.
- Una cuenta de correo para enviar los emails reales de verificación y recuperación de contraseña (Gmail, Outlook, etc.).

No se usa Composer ni ninguna librería externa: el envío de correos SMTP está
implementado a mano en `src/helpers/SmtpMailer.php`.

## Instalación paso a paso

### 1. Base de datos

Crear la base y cargar el esquema (incluye datos de ejemplo y el usuario administrador):

```bash
mysql -u root -p -e "CREATE DATABASE biblioteca_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p biblioteca_db < database/schema.sql
```

### 2. Variables de entorno

Copiar el archivo de ejemplo y completar los datos reales:

```bash
cp .env.example .env
```

Editar `.env`:

- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`: datos de tu MySQL local.
- `APP_URL`: la URL donde vas a correr el proyecto (por ejemplo `http://localhost:8000`).
- `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`: datos de tu cuenta de correo.
  - **Gmail**: activá la verificación en 2 pasos y generá una "contraseña de aplicación" en <https://myaccount.google.com/apppasswords>. Usá esa contraseña (no la de tu cuenta) en `SMTP_PASS`. `SMTP_HOST=smtp.gmail.com`, `SMTP_PORT=587`, `SMTP_SECURE=tls`.
  - **Outlook/Hotmail**: `SMTP_HOST=smtp.office365.com`, `SMTP_PORT=587`, `SMTP_SECURE=tls`.

> El archivo `.env` **nunca** se sube al repositorio (está en `.gitignore`) porque tiene datos sensibles.

### 3. Levantar el proyecto

**Opción rápida (servidor embebido de PHP), parado en la raíz del proyecto:**

```bash
php -S localhost:8000 -t public
```

Y entrar a <http://localhost:8000>.

**Opción con Apache/XAMPP:** apuntar el *Document Root* del virtual host a la carpeta `public/` del proyecto (nunca a la raíz, para que `src/`, `database/` y `.env` no queden expuestos por el navegador).

### 4. Ingresar

Usuario administrador precargado por el `schema.sql`:

- **Email:** `admin@biblioteca.com`
- **Contraseña:** `Admin123!`

Se recomienda cambiar esta contraseña después del primer ingreso (Usuarios → editar → nueva contraseña).

## Usuario administrador único

El sistema nunca permite que quede sin administradores: mientras exista un
único usuario activo con perfil marcado como "administrador", ese usuario no
puede darse de baja ni cambiarse de perfil (ver `Usuario::esUnicoAdministrador`
en `src/models/Usuario.php`). Para poder modificarlo, primero hay que crear
otro usuario con perfil Administrador.

## Perfiles y permisos

Vienen precargados dos perfiles:

- **Administrador**: acceso total a todos los módulos (no se puede restringir, por eso el mismo motivo que arriba).
- **Lector**: solo ve el Dashboard, el catálogo de Libros (solo lectura) y sus propios Préstamos.

Desde **Perfiles → Permisos** el administrador puede crear perfiles nuevos y
elegir, módulo por módulo, qué puede ver/crear/editar/eliminar cada perfil
(por ejemplo, restringir qué ve un Lector). Los cambios se aplican al momento.

## Estructura de carpetas

```
biblioteca-app/
├── database/
│   └── schema.sql            Esquema de MySQL + datos semilla
├── public/                   Document root del servidor web
│   ├── *.php                 Páginas (login, ABMs, reportes, etc.)
│   ├── maestro.php           ABM genérico para Autores/Categorías/Editoriales
│   └── assets/
│       ├── css/style.css     Única hoja de estilos (paleta beige + verde oliva)
│       └── js/                toast.js, modal.js, session.js, validaciones.js, reportes.js
├── src/
│   ├── bootstrap.php         Punto de arranque incluido por cada página
│   ├── config/                env.php (parser .env) y database.php (conexión PDO)
│   ├── helpers/                validaciones, auditoría, SmtpMailer, auth_guard
│   ├── models/                Acceso a datos por entidad (Usuario, Perfil, Libro, Prestamo, ...)
│   └── partials/               header.php / footer.php (layout común)
├── .env.example
└── .gitignore
```

## Checklist de la consigna → dónde está implementado

| Requisito | Dónde |
|---|---|
| Validación de campos requeridos / unicidad / formato de email | `src/helpers/functions.php`, usado en `registro.php`, `usuarios.php`, `maestro.php`, `libros.php` |
| Confirmación de contraseñas en el registro | `registro.php` + `validaciones.js` |
| Hash de contraseña | `password_hash()` (bcrypt) en `Usuario`/`registro.php`/`login.php` |
| Contraseña 8+ caracteres, mayúscula y carácter especial | `validar_password()` en `functions.php` (server) + `validaciones.js` (cliente) |
| Mensajes de contraseña inválida sin alert nativo | `login.php` + `toast.js` |
| Manejo de sesiones | `src/helpers/auth_guard.php` (`iniciar_sesion_segura`, `$_SESSION`) |
| Verificación de email por correo | `verificar_email.php`, `enviar_email_verificacion()` |
| Recuperación de contraseña por email | `recuperar_password.php`, `resetear_password.php` |
| ABM de usuarios con baja lógica | `usuarios.php` (`estado = 'baja'`) |
| ABM de perfiles | `perfiles.php` |
| Asignación de módulos a perfil / perfil a usuario | `permisos.php`, selector de perfil en `usuarios.php` |
| Único administrador no modificable | `Usuario::esUnicoAdministrador()` |
| Validación de rutas según perfil | `requerir_modulo()` en cada página |
| Redirección si no hay sesión activa | `requerir_sesion()` |
| Tablas maestras funcionales | `autores`, `categorias`, `editoriales` vía `maestro.php` + `libros.php` |
| Sin alerts nativos / notificaciones propias | `toast.js` + `modal.js` (confirmaciones y avisos) |
| Diseño responsive | `style.css` (sidebar colapsable, grillas fluidas) |
| Cache de sesión en localStorage + nombre arriba a la derecha | `session.js` + `footer.php` |
| Rollback de transacciones | `Prestamo::registrar()` / `registrarDevolucion()` (PDO `beginTransaction`/`rollBack`) |
| Estética consistente en todos los módulos | Un único `style.css` + `header.php`/`footer.php` compartidos |
| Auditoría | tabla `auditoria` + `registrar_auditoria()`, pantalla `auditoria.php` |
| Variables de entorno para datos sensibles | `.env` / `.env.example` |
| Informes y gráficos (3+) con export XLS/PDF | `reportes.php` + `reportes.js` (Chart.js, SheetJS, jsPDF) |
| Entornos por ramas DEV/QA/MAIN | ver sección siguiente |

## Flujo de ramas (DEV / QA / MAIN)

El repositorio se entrega con tres ramas:

- **DEV**: rama de trabajo diario, donde se integran los features.
- **QA**: se mergea desde DEV cuando algo está listo para probarse.
- **MAIN**: versión estable, se mergea desde QA una vez validado.

Flujo sugerido:

```bash
git checkout DEV
# ... desarrollo normal, commits ...
git checkout QA
git merge DEV
# ... pruebas ...
git checkout MAIN
git merge QA
```

`APP_ENV` en el `.env` de cada entorno puede setearse en `DEV`, `QA` o `PROD`
para diferenciar el nivel de detalle de errores mostrado (`src/bootstrap.php`).

## Notas

- El envío de correo real depende de que completes `SMTP_*` en tu `.env`. Si no lo configurás, el sistema sigue funcionando (los usuarios se crean igual) pero vas a ver un aviso de que no se pudo enviar el correo; en ese caso un administrador puede verificar la cuenta manualmente actualizando `email_verificado = 1` en la base, o recreando el usuario desde el ABM de Usuarios (que no exige verificación).
- Los datos de ejemplo (autores, categorías, editoriales, libros) están pensados solo para poder probar Libros/Préstamos apenas instalás el sistema; se pueden borrar o editar libremente desde la interfaz.
