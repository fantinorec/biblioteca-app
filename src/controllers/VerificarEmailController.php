<?php
require_once __DIR__ . '/../models/Usuario.php';

class VerificarEmailController
{
    public static function manejar(): void
    {
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

        renderizar_vista('auth/verificar_email', ['mensaje' => $mensaje, 'ok' => $ok]);
    }
}
