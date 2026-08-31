<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/models/Usuario.php';

$enviado = false;
$errorEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));

    if (!validar_email($email)) {
        $errorEmail = 'Ingresa un email con formato válido.';
    } else {
        $usuario = Usuario::buscarPorEmail($email);
        // Por seguridad mostramos siempre el mismo mensaje exista o no la cuenta,
        // pero solo enviamos el correo si realmente existe y está activa.
        if ($usuario && $usuario['estado'] === 'activo') {
            $token = generar_token();
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            Usuario::establecerTokenReset((int) $usuario['id'], $token, $expira);
            enviar_email_recuperacion($usuario['email'], $usuario['nombre'], $token);
            registrar_auditoria((int) $usuario['id'], $usuario['email'], 'RESET_PASSWORD_SOLICITADO', 'usuarios', (int) $usuario['id'], 'Solicitud de recuperación de contraseña');
        }
        $enviado = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña · <?= h(env('APP_NAME', 'Biblioteca')) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="pagina-auth">
    <div class="card">
        <div class="pagina-auth__logo">📚 <?= h(env('APP_NAME', 'Biblioteca')) ?></div>
        <div class="pagina-auth__subtitulo">Recuperar contraseña</div>

        <?php if ($enviado): ?>
            <div class="card" style="background:#e3ecdf;border-color:#bcd4b4;">
                Si el email ingresado corresponde a una cuenta activa, te enviamos un correo con las instrucciones para restablecer tu contraseña. El enlace vence en 1 hora.
            </div>
            <p class="texto-centro mt-2"><a href="login.php">Volver a iniciar sesión</a></p>
        <?php else: ?>
            <form method="post" novalidate>
                <div class="form-grupo">
                    <label for="email">Email de tu cuenta</label>
                    <input class="form-control" type="email" id="email" name="email" required autofocus>
                    <span class="form-error"><?= h($errorEmail) ?></span>
                </div>
                <button type="submit" class="btn btn-primario btn-bloque">Enviar instrucciones</button>
            </form>
            <p class="texto-centro mt-2 texto-suave"><a href="login.php">Volver a iniciar sesión</a></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
