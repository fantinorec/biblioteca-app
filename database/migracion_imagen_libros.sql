-- =====================================================================
-- Migracion: agrega la columna de imagen de portada a libros
-- Ejecutar UNA sola vez si la base de datos ya existia antes de este
-- cambio (si es una instalacion nueva, esta columna ya viene incluida
-- en database/schema.sql y no hace falta correr este archivo).
--
-- Como aplicarla en phpMyAdmin:
--   1) Entra a tu base de datos (biblioteca_db) en phpMyAdmin.
--   2) Anda a la pestaña "SQL".
--   3) Pega el contenido de este archivo y hace click en "Continuar".
-- =====================================================================

ALTER TABLE libros ADD COLUMN imagen_url VARCHAR(500) DEFAULT NULL AFTER stock_disponible;
