<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/models/Usuario.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$usuario = $token !== '' ? Usuario::buscarPorTokenReset($token) : null;
$tokenValido = $usuario && (!empty($usuario['token_reset_expira']) && strtotime($usuario['token_reset_expira']) >= time());

$errores = [];
$actualizado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValido) {
    $password = $_POST['password'] ?? '';
    $passwordConfirmacion = $_POST['password_confirmacion'] ?? '';

    if (!validar_password($password)) {
        $errores['password'] = mensaje_politica_password();
    } elseif ($password !== $passwordConfirmacion) {
        $errores['password_confirmacion'] = 'Las contraseñas no coinciden.';
    } else {
        Usuario::actualizarPassword((int) $usuario['id'], password_hash($password, PASSWORD_BCRYPT));
        registrar_auditoria((int) $usuario['id'], $usuario['email'], 'RESET_PASSWORD', 'usuarios', (int) $usuario['id'], 'Contraseña restablecida vía email');
        $actualizado = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña · <?= h(env('APP_NAME', 'Biblioteca')) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="pagina-auth">
    <div class="card">
        <div class="pagina-auth__logo">📚 <?= h(env('APP_NAME', 'Biblioteca')) ?></div>
        <div class="pagina-auth__subtitulo">Restablecer contraseña</div>

        <?php if (!$tokenValido && !$actualizado): ?>
            <div class="card" style="background:#f3dede;border-color:#e3b8b8;">
                El enlace no es válido o venció. Solicitá uno nuevo desde "¿Olvidaste tu contraseña?".
            </div>
            <p class="texto-centro mt-2"><a href="recuperar_password.php">Solicitar nuevo enlace</a></p>
        <?php elseif ($actualizado): ?>
            <div class="card" style="background:#e3ecdf;border-color:#bcd4b4;">Tu contraseña fue actualizada correctamente.</div>
            <p class="texto-centro mt-2"><a href="login.php">Iniciar sesión</a></p>
        <?php else: ?>
            <form method="post" id="formReset" novalidate>
                <input type="hidden" name="token" value="<?= h($token) ?>">
                <div class="form-grupo">
                    <label for="password">Nueva contraseña</label>
                    <input class="form-control" type="password" id="password" name="password" data-validar="password" required>
                    <span class="form-ayuda"><?= h(mensaje_politica_password()) ?></span>
                    <span class="form-error"><?= h($errores['password'] ?? '') ?></span>
                </div>
                <div class="form-grupo">
                    <label for="password_confirmacion">Confirmar nueva contraseña</label>
                    <input class="form-control" type="password" id="password_confirmacion" name="password_confirmacion" data-validar="password-confirmacion" required>
                    <span class="form-error"><?= h($errores['password_confirmacion'] ?? '') ?></span>
                </div>
                <button type="submit" class="btn btn-primario btn-bloque">Actualizar contraseña</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<script src="assets/js/toast.js"></script>
<script src="assets/js/validaciones.js"></script>
<script>activarValidacionFormulario('formReset');</script>
</body>
</html>
