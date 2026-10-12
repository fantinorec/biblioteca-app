-- Migracion: agrega el flujo de solicitud/aprobacion de prestamos.
-- Antes, un Lector "creaba" un prestamo y quedaba prestado al instante.
-- Ahora el Lector SOLICITA, y un bibliotecario/administrador aprueba o
-- rechaza esa solicitud. Se agregan dos estados nuevos: "pendiente" y
-- "rechazado".
--
-- Como correrla en phpMyAdmin:
--   1. Entra a http://localhost/phpmyadmin
--   2. Elegi tu base (biblioteca_db) en el panel izquierdo.
--   3. Anda a la pestaña "SQL".
--   4. Pega esta consulta y apreta "Continuar" / "Go".

ALTER TABLE prestamos
    MODIFY estado ENUM('pendiente','prestado','devuelto','vencido','rechazado') NOT NULL DEFAULT 'prestado';
