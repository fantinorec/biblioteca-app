<?php
require_once __DIR__ . '/../models/Usuario.php';

class ResetearPasswordController
{
    public static function manejar(): void
    {
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        $usuario = $token !== '' ? Usuario::buscarPorTokenReset($token) : null;
        $tokenValido = $usuario && (!empty($usuario['token_reset_expira']) && strtotime($usuario['token_reset_expira']) >= time());

        $errores = [];
        $actualizado = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValido) {
            $password = $_POST['password'] ?? '';
            $passwordConfirmacion = $_POST['password_confirmacion'] ?? '';

            if (!validar_password($password)) {
                $errores['password'] = mensaje_password_especifico($password);
            } elseif ($password !== $passwordConfirmacion) {
                $errores['password_confirmacion'] = 'Las contraseñas no coinciden.';
            } else {
                Usuario::actualizarPassword((int) $usuario['id'], password_hash($password, PASSWORD_BCRYPT));
                registrar_auditoria((int) $usuario['id'], $usuario['email'], 'RESET_PASSWORD', 'usuarios', (int) $usuario['id'], 'Contraseña restablecida vía email');
                $actualizado = true;
            }
        }

        renderizar_vista('auth/resetear_password', [
            'token'        => $token,
            'tokenValido'  => $tokenValido,
            'errores'      => $errores,
            'actualizado'  => $actualizado,
        ]);
    }
}
