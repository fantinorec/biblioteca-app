<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/models/Libro.php';
require_once __DIR__ . '/../../src/helpers/GeminiApiClient.php';
require_once __DIR__ . '/../../src/helpers/recomendador_libros.php';

header('Content-Type: application/json; charset=utf-8');

requerir_sesion();

if (!tiene_permiso('libros', 'ver')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'No tenés permiso para usar esto.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

$pedido = trim($_POST['pedido'] ?? '');
if ($pedido === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'Contanos qué tipo de libro estás buscando.']);
    exit;
}
if (mb_strlen($pedido) > 300) {
    $pedido = mb_substr($pedido, 0, 300);
}

try {
    $recomendacion = generar_recomendacion_libros($pedido);
    echo json_encode(['ok' => true, 'mensaje' => $recomendacion], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Error del recomendador de libros con IA: ' . $e->getMessage());
    $mensaje = 'No se pudo generar la recomendación en este momento. Probá de nuevo en un rato.';
    if (env('APP_ENV', 'DEV') === 'DEV') {
        // Solo en desarrollo mostramos el detalle real, para poder diagnosticar rápido.
        $mensaje .= ' (detalle: ' . $e->getMessage() . ')';
    }
    echo json_encode(['ok' => false, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
}
