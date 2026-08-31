<?php
/**
 * Punto unico de arranque incluido por todas las paginas publicas.
 * Carga configuracion, helpers y arranca la sesion.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0'); // los errores van a error_log, no se muestran al usuario final

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/helpers/auditoria.php';
require_once __DIR__ . '/helpers/mailer.php';
require_once __DIR__ . '/helpers/auth_guard.php';

cargar_env(dirname(__DIR__) . '/.env');
iniciar_sesion_segura();

if (env('APP_ENV', 'DEV') === 'DEV') {
    ini_set('display_errors', '1');
}
