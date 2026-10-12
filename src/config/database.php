<?php

require_once __DIR__ . '/env.php';

function obtenerConexion(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    cargar_env(dirname(__DIR__, 2) . '/.env');

    $host = env('DB_HOST', '127.0.0.1');
    $port = env('DB_PORT', '3306');
    $db   = env('DB_NAME', '');
    $user = env('DB_USER', '');
    $pass = env('DB_PASS', '');

    if ($db === '' || $user === '') {
        error_log('Faltan variables de entorno DB_NAME/DB_USER: revisar el archivo .env.');
        die('No se pudo conectar a la base de datos. Contacte al administrador.');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Si falla, nunca mostramos el motivo real en pantalla (podría revelar datos de la base).
        error_log('Error de conexion a la base de datos: ' . $e->getMessage());
        die('No se pudo conectar a la base de datos. Contacte al administrador.');
    }

    return $pdo;
}
