<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Solo un administrador logueado puede bajar el backup completo (incluye datos de usuarios).
requerir_sesion();
if (empty($_SESSION['es_administrador'])) {
    http_response_code(403);
    die('No tenés permiso para descargar el backup de la base de datos.');
}

$pdo = obtenerConexion();
$nombreDb = env('DB_NAME', 'biblioteca_db');
$nombreArchivo = 'backup_' . $nombreDb . '_' . date('Y-m-d_His') . '.sql';

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('X-Content-Type-Options: nosniff');

echo "-- Backup de la base de datos `{$nombreDb}`\n";
echo "-- Generado automáticamente el " . date('Y-m-d H:i:s') . "\n\n";
echo "SET NAMES utf8mb4;\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

$tablas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

foreach ($tablas as $tabla) {
    echo "-- --------------------------------------------------------\n";
    echo "-- Tabla `{$tabla}`\n";
    echo "-- --------------------------------------------------------\n\n";

    echo "DROP TABLE IF EXISTS `{$tabla}`;\n";
    $create = $pdo->query("SHOW CREATE TABLE `{$tabla}`")->fetch(PDO::FETCH_ASSOC);
    echo $create['Create Table'] . ";\n\n";

    $filas = $pdo->query("SELECT * FROM `{$tabla}`")->fetchAll(PDO::FETCH_ASSOC);
    if ($filas) {
        $columnas = array_keys($filas[0]);
        $columnasSql = '`' . implode('`, `', $columnas) . '`';
        foreach ($filas as $fila) {
            $valores = array_map(function ($valor) use ($pdo) {
                return $valor === null ? 'NULL' : $pdo->quote($valor);
            }, array_values($fila));
            echo "INSERT INTO `{$tabla}` ({$columnasSql}) VALUES (" . implode(', ', $valores) . ");\n";
        }
        echo "\n";
    }
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";
