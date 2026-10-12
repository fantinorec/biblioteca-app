-- Migracion: le da al perfil Lector permiso de "ver" sobre el modulo
-- Auditoria, para que pueda entrar a "Mis movimientos" (solo ve sus
-- propias acciones, nunca las de otros usuarios).
--
-- Si en tu base los IDs de "Lector" o "Auditoria" son distintos a los de
-- instalacion por defecto (2 y 11), ajustalos antes de correr esto. Podes
-- confirmarlos mirando las tablas "perfiles" y "modulos" en phpMyAdmin.
--
-- Como correrla en phpMyAdmin:
--   1. Entra a http://localhost/phpmyadmin
--   2. Elegi tu base (biblioteca_db) en el panel izquierdo.
--   3. Anda a la pestaña "SQL".
--   4. Pega esta consulta y apreta "Continuar" / "Go".

INSERT INTO perfil_modulo (perfil_id, modulo_id, puede_ver, puede_crear, puede_editar, puede_eliminar)
VALUES (2, 11, 1, 0, 0, 0)
ON DUPLICATE KEY UPDATE puede_ver = 1;
