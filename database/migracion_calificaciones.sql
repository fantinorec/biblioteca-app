-- =====================================================================
-- Migración: calificación por estrellas de los lectores
-- Correr este script completo en phpMyAdmin (pestaña SQL de biblioteca_db)
-- =====================================================================

-- Cada usuario arranca con 5 estrellas y una "racha" de devoluciones
-- buenas seguidas (se usa para subir la calificación con el tiempo).
ALTER TABLE usuarios
    ADD COLUMN calificacion TINYINT UNSIGNED NOT NULL DEFAULT 5 AFTER estado,
    ADD COLUMN racha_buena TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER calificacion;

-- En qué estado volvió el libro al devolverlo (lo marca el bibliotecario/administrador).
ALTER TABLE prestamos
    ADD COLUMN estado_devolucion ENUM('bueno', 'danado') DEFAULT NULL AFTER fecha_devolucion_real;

-- Historial de cada cambio de calificación (para mostrarle al lector el motivo si reclama).
CREATE TABLE historial_calificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    prestamo_id INT DEFAULT NULL,
    delta TINYINT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historial_calificacion_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_historial_calificacion_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE SET NULL,
    INDEX idx_historial_calificacion_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nuevo módulo "Calificaciones" en el menú, visible solo para el perfil Administrador.
INSERT INTO modulos (nombre, clave, descripcion, icono, ruta, orden, estado)
VALUES ('Calificaciones', 'calificaciones', 'Calificación por estrellas de cada lector', 'star', 'calificaciones.php', 12, 'activo');

INSERT INTO perfil_modulo (perfil_id, modulo_id, puede_ver, puede_crear, puede_editar, puede_eliminar)
SELECT 1, id, 1, 0, 0, 0 FROM modulos WHERE clave = 'calificaciones';
