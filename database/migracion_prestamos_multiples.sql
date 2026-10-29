-- Migracion: permite prestar/solicitar varios libros a la vez.
-- Agrega una columna "grupo_id" a la tabla prestamos: todas las filas que
-- comparten el mismo grupo_id viajan juntas (se aprueban, rechazan,
-- cancelan y devuelven siempre todas a la vez). Un prestamo de un solo
-- libro no se ve afectado.
--
-- Como correrla en phpMyAdmin:
--   1. Entra a http://localhost/phpmyadmin
--   2. Elegi tu base (biblioteca_db) en el panel izquierdo.
--   3. Anda a la pestaña "SQL".
--   4. Pega esta consulta y apreta "Continuar" / "Go".

ALTER TABLE prestamos
    ADD COLUMN grupo_id INT DEFAULT NULL AFTER id,
    ADD INDEX idx_prestamos_grupo_id (grupo_id);
