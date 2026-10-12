<?php

function validar_campos_requeridos(array $datos, array $campos): array
{
    $errores = [];
    foreach ($campos as $campo => $etiqueta) {
        $valor = $datos[$campo] ?? '';
        if (trim((string) $valor) === '') {
            $errores[$campo] = "El campo \"$etiqueta\" es obligatorio.";
        }
    }
    return $errores;
}

function validar_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validar_password(string $password): bool
{
    if (strlen($password) < 8) {
        return false;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return false;
    }
    return true;
}

function mensaje_politica_password(): string
{
    return 'La contraseña debe tener al menos 8 caracteres, una letra mayúscula y un carácter especial.';
}

function faltantes_password(string $password): array
{
    $faltantes = [];
    if (strlen($password) < 8) {
        $faltantes[] = 'al menos 8 caracteres';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $faltantes[] = 'una letra mayúscula';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $faltantes[] = 'un carácter especial (por ejemplo: ! @ # $ % &)';
    }
    return $faltantes;
}

function mensaje_password_especifico(string $password): string
{
    $faltantes = faltantes_password($password);
    if (empty($faltantes)) {
        return '';
    }
    if (count($faltantes) === 1) {
        return 'A tu contraseña le falta ' . $faltantes[0] . '.';
    }
    $ultimo = array_pop($faltantes);
    return 'A tu contraseña le falta ' . implode(', ', $faltantes) . ' y ' . $ultimo . '.';
}

function h(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function generar_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

function ip_cliente(): string
{
    foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $clave) {
        if (!empty($_SERVER[$clave])) {
            $ip = explode(',', $_SERVER[$clave])[0];
            return trim($ip);
        }
    }
    return '0.0.0.0';
}

function responder_json(array $datos, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function leer_json_body(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function requerir_metodo(string $metodo): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $metodo) {
        responder_json(['ok' => false, 'mensaje' => 'Metodo no permitido.'], 405);
    }
}

function redirigir(string $ruta): never
{
    header('Location: ' . $ruta);
    exit;
}

function asset_url(string $rutaRelativa): string
{
    $rutaAbsoluta = __DIR__ . '/../../public/' . ltrim($rutaRelativa, '/');
    $version = @filemtime($rutaAbsoluta) ?: time();
    return $rutaRelativa . '?v=' . $version;
}
