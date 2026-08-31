<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/models/Usuario.php';

$token = $_GET['token'] ?? '';
$mensaje = '';
$ok = false;

if ($token === '') {
    $mensaje = 'El enlace de verificación no es válido.';
} else {
    $usuario = Usuario::buscarPorTokenVerificacion($token);
    if (!$usuario) {
        $mensaje = 'El enlace de verificación no es válido o ya fue utilizado.';
    } elseif (!empty($usuario['token_verificacion_expira']) && strtotime($usuario['token_verificacion_expira']) < time()) {
        $mensaje = 'El enlace de verificación venció. Pedí que te reenvíen uno nuevo.';
    } else {
        Usuario::marcarEmailVerificado((int) $usuario['id']);
        registrar_auditoria((int) $usuario['id'], $usuario['email'], 'EDITAR', 'usuarios', (int) $usuario['id'], 'Email verificado');
        $ok = true;
        $mensaje = 'Tu cuenta fue verificada correctamente. Ya podés iniciar sesión.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de cuenta · <?= h(env('APP_NAME', 'Biblioteca')) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="pagina-auth">
    <div class="card texto-centro">
        <div class="pagina-auth__logo">📚 <?= h(env('APP_NAME', 'Biblioteca')) ?></div>
        <div class="card" style="background:<?= $ok ? '#e3ecdf' : '#f3dede' ?>;border-color:<?= $ok ? '#bcd4b4' : '#e3b8b8' ?>;">
            <?= h($mensaje) ?>
        </div>
        <a href="login.php" class="btn btn-primario mt-2">Ir a iniciar sesión</a>
    </div>
</div>
</body>
</html>
