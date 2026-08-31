-- =====================================================================
-- Sistema de Gestion de Biblioteca - Primer Parcial
-- Esquema de base de datos MySQL
-- =====================================================================
-- Como importar (ejemplo con cliente mysql):
--   mysql -u root -p -e "CREATE DATABASE biblioteca_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
--   mysql -u root -p biblioteca_db < database/schema.sql
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Tabla: perfiles (roles del sistema)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS perfiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE,
    descripcion VARCHAR(255) DEFAULT NULL,
    es_administrador TINYINT(1) NOT NULL DEFAULT 0,
    estado ENUM('activo','baja') NOT NULL DEFAULT 'activo',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabla: modulos (pantallas/funcionalidades del sistema)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE,
    clave VARCHAR(60) NOT NULL UNIQUE,        -- identificador interno usado en rutas/guards
    descripcion VARCHAR(255) DEFAULT NULL,
    icono VARCHAR(40) DEFAULT 'circle',
    ruta VARCHAR(120) NOT NULL,               -- archivo/ruta publica del modulo
    orden INT NOT NULL DEFAULT 0,
    estado ENUM('activo','baja') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabla intermedia: perfil_modulo (que modulos ve cada perfil)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS perfil_modulo (
    perfil_id INT NOT NULL,
    modulo_id INT NOT NULL,
    puede_ver TINYINT(1) NOT NULL DEFAULT 1,
    puede_crear TINYINT(1) NOT NULL DEFAULT 0,
    puede_editar TINYINT(1) NOT NULL DEFAULT 0,
    puede_eliminar TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (perfil_id, modulo_id),
    CONSTRAINT fk_pm_perfil FOREIGN KEY (perfil_id) REFERENCES perfiles(id) ON DELETE CASCADE,
    CONSTRAINT fk_pm_modulo FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabla: usuarios
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    apellido VARCHAR(80) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    perfil_id INT NOT NULL,
    email_verificado TINYINT(1) NOT NULL DEFAULT 0,
    token_verificacion VARCHAR(100) DEFAULT NULL,
    token_verificacion_expira DATETIME DEFAULT NULL,
    token_reset VARCHAR(100) DEFAULT NULL,
    token_reset_expira DATETIME DEFAULT NULL,
    intentos_fallidos INT NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME DEFAULT NULL,
    estado ENUM('activo','baja') NOT NULL DEFAULT 'activo',
    ultimo_login DATETIME DEFAULT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_perfil FOREIGN KEY (perfil_id) REFERENCES perfiles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabla: auditoria
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    usuario_email VARCHAR(150) DEFAULT NULL,
    accion VARCHAR(30) NOT NULL,          -- CREAR, EDITAR, BAJA, LOGIN, LOGIN_FALLIDO, LOGOUT, RESET_PASSWORD, etc.
    tabla_afectada VARCHAR(60) DEFAULT NULL,
    registro_id INT DEFAULT NULL,
    detalle TEXT DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tablas maestras del dominio biblioteca
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS autores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    apellido VARCHAR(80) NOT NULL,
    nacionalidad VARCHAR(80) DEFAULT NULL,
    estado ENUM('activo','baja') NOT NULL DEFAULT 'activo',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE,
    descripcion VARCHAR(255) DEFAULT NULL,
    estado ENUM('activo','baja') NOT NULL DEFAULT 'activo',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS editoriales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    pais VARCHAR(80) DEFAULT NULL,
    estado ENUM('activo','baja') NOT NULL DEFAULT 'activo',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS libros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(180) NOT NULL,
    isbn VARCHAR(30) DEFAULT NULL UNIQUE,
    autor_id INT NOT NULL,
    categoria_id INT NOT NULL,
    editorial_id INT NOT NULL,
    anio_publicacion INT DEFAULT NULL,
    stock_total INT NOT NULL DEFAULT 1,
    stock_disponible INT NOT NULL DEFAULT 1,
    estado ENUM('activo','baja') NOT NULL DEFAULT 'activo',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_libro_autor FOREIGN KEY (autor_id) REFERENCES autores(id),
    CONSTRAINT fk_libro_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    CONSTRAINT fk_libro_editorial FOREIGN KEY (editorial_id) REFERENCES editoriales(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prestamos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,              -- lector que retira el libro
    libro_id INT NOT NULL,
    usuario_registro_id INT DEFAULT NULL, -- quien registro el prestamo (bibliotecario/admin)
    fecha_prestamo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion_estimada DATE NOT NULL,
    fecha_devolucion_real DATETIME DEFAULT NULL,
    estado ENUM('prestado','devuelto','vencido') NOT NULL DEFAULT 'prestado',
    observaciones VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_prestamo_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_prestamo_libro FOREIGN KEY (libro_id) REFERENCES libros(id),
    CONSTRAINT fk_prestamo_registro FOREIGN KEY (usuario_registro_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- DATOS SEMILLA
-- =====================================================================

-- Perfiles base
INSERT INTO perfiles (id, nombre, descripcion, es_administrador, estado) VALUES
    (1, 'Administrador', 'Perfil con acceso total al sistema', 1, 'activo'),
    (2, 'Lector', 'Perfil de usuario final que consulta el catalogo y sus prestamos', 0, 'activo');

-- Modulos del sistema
INSERT INTO modulos (id, nombre, clave, descripcion, icono, ruta, orden, estado) VALUES
    (1,  'Dashboard',    'dashboard',   'Panel principal con indicadores',        'home',      'index.php',       1, 'activo'),
    (2,  'Usuarios',     'usuarios',    'ABM de usuarios del sistema',            'users',     'usuarios.php',    2, 'activo'),
    (3,  'Perfiles',     'perfiles',    'ABM de perfiles',                        'shield',    'perfiles.php',    3, 'activo'),
    (4,  'Permisos',     'permisos',    'Asignacion de modulos a perfiles',       'lock',      'permisos.php',    4, 'activo'),
    (5,  'Libros',       'libros',      'Catalogo de libros',                     'book',      'libros.php',      5, 'activo'),
    (6,  'Autores',      'autores',     'ABM de autores',                         'pen',       'maestro.php?tipo=autores',     6, 'activo'),
    (7,  'Categorias',   'categorias',  'ABM de categorias',                      'tag',       'maestro.php?tipo=categorias',  7, 'activo'),
    (8,  'Editoriales',  'editoriales', 'ABM de editoriales',                     'building',  'maestro.php?tipo=editoriales', 8, 'activo'),
    (9,  'Prestamos',    'prestamos',   'Prestamos y devoluciones de libros',     'exchange',  'prestamos.php',   9, 'activo'),
    (10, 'Reportes',     'reportes',    'Graficos e indicadores exportables',     'chart',     'reportes.php',   10, 'activo'),
    (11, 'Auditoria',    'auditoria',   'Historial de acciones del sistema',      'history',   'auditoria.php',  11, 'activo');

-- Permisos: Administrador ve y puede todo
INSERT INTO perfil_modulo (perfil_id, modulo_id, puede_ver, puede_crear, puede_editar, puede_eliminar)
SELECT 1, id, 1, 1, 1, 1 FROM modulos;

-- Permisos: Lector solo Dashboard, Libros (solo lectura) y sus Prestamos
INSERT INTO perfil_modulo (perfil_id, modulo_id, puede_ver, puede_crear, puede_editar, puede_eliminar) VALUES
    (2, 1, 1, 0, 0, 0),  -- Dashboard
    (2, 5, 1, 0, 0, 0),  -- Libros (solo consulta)
    (2, 9, 1, 1, 0, 0);  -- Prestamos (puede ver los propios / solicitar)

-- Usuario administrador inicial
-- Email: admin@biblioteca.com | Password: Admin123!  (cumple regla: 8+ caracteres, mayuscula y caracter especial)
INSERT INTO usuarios (nombre, apellido, email, password_hash, perfil_id, email_verificado, estado)
VALUES ('Admin', 'Sistema', 'admin@biblioteca.com',
        '$2y$12$ZV0A/4IlU1N61HVbm7ZciOmT2aPID4Zf0L0.jDC0SmCoZlg6OaEhC',
        1, 1, 'activo');

-- Datos de ejemplo para tablas maestras (para poder probar Libros/Prestamos sin cargar todo a mano)
INSERT INTO autores (nombre, apellido, nacionalidad) VALUES
    ('Jorge Luis', 'Borges', 'Argentina'),
    ('Gabriel', 'Garcia Marquez', 'Colombia'),
    ('Julio', 'Cortazar', 'Argentina');

INSERT INTO categorias (nombre, descripcion) VALUES
    ('Ficcion', 'Novelas y cuentos de ficcion'),
    ('Ciencia', 'Divulgacion cientifica'),
    ('Historia', 'Libros de historia y ensayo');

INSERT INTO editoriales (nombre, pais) VALUES
    ('Sudamericana', 'Argentina'),
    ('Planeta', 'España'),
    ('Fondo de Cultura Economica', 'Mexico');

INSERT INTO libros (titulo, isbn, autor_id, categoria_id, editorial_id, anio_publicacion, stock_total, stock_disponible) VALUES
    ('Ficciones', '978-950-000-001', 1, 1, 1, 1944, 5, 5),
    ('Cien años de soledad', '978-950-000-002', 2, 1, 2, 1967, 4, 4),
    ('Rayuela', '978-950-000-003', 3, 1, 1, 1963, 3, 3);
