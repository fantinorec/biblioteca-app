<?php
require_once __DIR__ . '/../models/Usuario.php';

class RegistroController
{
    private const PERFIL_LECTOR_ID = 2;

    public static function manejar(): void
    {
        if (usuario_autenticado()) {
            redirigir('index.php');
        }

        $errores = [];
        $viejo = ['nombre' => '', 'apellido' => '', 'email' => ''];
        $registroOk = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$errores, $viejo, $registroOk] = self::procesarRegistro();
        }

        renderizar_vista('auth/registro', [
            'errores'    => $errores,
            'viejo'      => $viejo,
            'registroOk' => $registroOk,
        ]);
    }

    private static function procesarRegistro(): array
    {
        $viejo = [
            'nombre'   => trim($_POST['nombre'] ?? ''),
            'apellido' => trim($_POST['apellido'] ?? ''),
            'email'    => trim(strtolower($_POST['email'] ?? '')),
        ];
        $password = $_POST['password'] ?? '';
        $passwordConfirmacion = $_POST['password_confirmacion'] ?? '';

        $errores = validar_campos_requeridos($_POST, [
            'nombre'   => 'Nombre',
            'apellido' => 'Apellido',
            'email'    => 'Email',
            'password' => 'Contraseña',
            'password_confirmacion' => 'Confirmación de contraseña',
        ]);

        if (empty($errores['email']) && !validar_email($viejo['email'])) {
            $errores['email'] = 'El formato del email no es valido.';
        }

        if (empty($errores['email']) && Usuario::existeEmail($viejo['email'])) {
            $errores['email'] = 'Ya existe una cuenta registrada con ese email.';
        }

        if (empty($errores['password']) && !validar_password($password)) {
            $errores['password'] = mensaje_password_especifico($password);
        }

        if (empty($errores['password']) && $password !== $passwordConfirmacion) {
            $errores['password_confirmacion'] = 'Las contraseñas no coinciden.';
        }

        if (!empty($errores)) {
            return [$errores, $viejo, false];
        }

        $token = generar_token();
        $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $idNuevo = Usuario::crear([
            'nombre'                    => $viejo['nombre'],
            'apellido'                  => $viejo['apellido'],
            'email'                     => $viejo['email'],
            'password_hash'             => password_hash($password, PASSWORD_BCRYPT),
            'perfil_id'                 => self::PERFIL_LECTOR_ID,
            'email_verificado'          => 0,
            'token_verificacion'        => $token,
            'token_verificacion_expira' => $expira,
            'estado'                    => 'activo',
        ]);

        $envioOk = enviar_email_verificacion($viejo['email'], $viejo['nombre'], $token);
        registrar_auditoria($idNuevo, $viejo['email'], 'CREAR', 'usuarios', $idNuevo, 'Alta por autoregistro');

        if (!$envioOk) {
            $errores['general'] = 'La cuenta se creó, pero no pudimos enviar el correo de verificación. Revisa la configuración SMTP del archivo .env o pedile a un administrador que verifique tu cuenta manualmente.';
            return [$errores, $viejo, false];
        }

        return [$errores, $viejo, true];
    }
}
