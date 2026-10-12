<?php

require_once __DIR__ . '/src/config/env.php';
cargar_env(__DIR__ . '/.env');

$host = env('DB_HOST', '127.0.0.1');
$user = env('DB_USER', '');
$pass = env('DB_PASS', '');
$name = env('DB_NAME', '');
$port = env('DB_PORT', '3306');

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Si falla, no mostramos el motivo real en pantalla (podría revelar datos de la base).
    error_log('Error de conexion a la base de datos: ' . $e->getMessage());
}

$conexion = $pdo ?? null;
