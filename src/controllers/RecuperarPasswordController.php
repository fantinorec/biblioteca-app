<?php
require_once __DIR__ . '/../models/Usuario.php';

class RecuperarPasswordController
{
    public static function manejar(): void
    {
        $enviado = false;
        $errorEmail = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim(strtolower($_POST['email'] ?? ''));

            if (!validar_email($email)) {
                $errorEmail = 'Ingresa un email con formato válido.';
            } else {
                $usuario = Usuario::buscarPorEmail($email);

                if ($usuario && $usuario['estado'] === 'activo') {
                    $token = generar_token();
                    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    Usuario::establecerTokenReset((int) $usuario['id'], $token, $expira);
                    enviar_email_recuperacion($usuario['email'], $usuario['nombre'], $token);
                    registrar_auditoria((int) $usuario['id'], $usuario['email'], 'RESET_PASSWORD_SOLICITADO', 'usuarios', (int) $usuario['id'], 'Solicitud de recuperación de contraseña');
                }
                // Mostramos el mismo mensaje exista o no la cuenta, para no dar pistas de qué emails están registrados.
                $enviado = true;
            }
        }

        renderizar_vista('auth/recuperar_password', ['enviado' => $enviado, 'errorEmail' => $errorEmail]);
    }
}
