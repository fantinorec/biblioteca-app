<?php

// Junta los datos del controlador con la pantalla que los va a mostrar.
function renderizar_vista(string $vista, array $datos = []): void
{
    extract($datos, EXTR_SKIP);
    $rutaVista = __DIR__ . '/../views/' . $vista . '.php';
    if (!file_exists($rutaVista)) {
        throw new RuntimeException("Vista no encontrada: $vista");
    }
    require $rutaVista;
}
