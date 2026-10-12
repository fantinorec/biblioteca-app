<?php

function cargar_env(string $rutaArchivo): void
{
    static $cargado = false;
    if ($cargado) {
        return;
    }

    if (!file_exists($rutaArchivo)) {
        $ejemplo = dirname($rutaArchivo) . '/.env.example';
        if (file_exists($ejemplo)) {
            die('Falta el archivo .env en la raiz del proyecto. Copia .env.example como .env y completa los datos (base de datos y SMTP).');
        }
        die('No se encontro el archivo de configuracion .env');
    }

    $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }
        if (!str_contains($linea, '=')) {
            continue;
        }
        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor);

        if ((str_starts_with($valor, '"') && str_ends_with($valor, '"')) ||
            (str_starts_with($valor, "'") && str_ends_with($valor, "'"))) {
            $valor = substr($valor, 1, -1);
        }
        $_ENV[$clave] = $valor;
        putenv("$clave=$valor");
    }

    $cargado = true;
}

function env(string $clave, $porDefecto = null)
{
    $valor = $_ENV[$clave] ?? getenv($clave);
    if ($valor === false || $valor === null) {
        return $porDefecto;
    }
    return $valor;
}
