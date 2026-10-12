<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Perfil.php';

class LoginController
{
    private const MAX_INTENTOS_FALLIDOS = 5;
    private const MINUTOS_BLOQUEO = 15;

    public static function manejar(): void
    {
        if (usuario_autenticado()) {
            redirigir('index.php');
        }

        $errorGeneral = '';
        $emailViejo = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$errorGeneral, $emailViejo] = self::procesarLogin();
        }

        $motivo = $_GET['motivo'] ?? '';

        renderizar_vista('auth/login', [
            'errorGeneral' => $errorGeneral,
            'emailViejo'   => $emailViejo,
            'motivo'       => $motivo,
        ]);
    }

    private static function procesarLogin(): array
    {
        $email = trim(strtolower($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        $errores = validar_campos_requeridos($_POST, ['email' => 'Email', 'password' => 'Contraseña']);
        if (!empty($errores)) {
            return ['Completa tu email y contraseña.', $email];
        }

        $usuario = Usuario::buscarPorEmail($email);

        if (!$usuario) {
            registrar_auditoria(null, $email, 'LOGIN_FALLIDO', 'usuarios', null, 'Email no registrado');
            return ['Usuario o contraseña incorrectos.', $email];
        }

        if ($usuario['estado'] !== 'activo') {
            return ['Esta cuenta se encuentra dada de baja. Contacta a un administrador.', $email];
        }

        if (!empty($usuario['bloqueado_hasta']) && strtotime($usuario['bloqueado_hasta']) > time()) {
            return ['Tu cuenta esta bloqueada temporalmente por intentos fallidos. Probá de nuevo en unos minutos.', $email];
        }

        if ((int) $usuario['email_verificado'] === 0) {
            return ['Todavia no confirmaste tu email. Revisa tu casilla de correo para activar la cuenta.', $email];
        }

        if (!password_verify($password, $usuario['password_hash'])) {
            return [self::registrarIntentoFallido($usuario, $email), $email];
        }

        self::iniciarSesion($usuario);
        registrar_auditoria((int) $usuario['id'], $email, 'LOGIN', 'usuarios', (int) $usuario['id'], 'Inicio de sesion exitoso');
        redirigir('index.php');
    }

    private static function registrarIntentoFallido(array $usuario, string $email): string
    {
        Usuario::registrarLoginFallido((int) $usuario['id']);
        registrar_auditoria((int) $usuario['id'], $email, 'LOGIN_FALLIDO', 'usuarios', (int) $usuario['id'], 'Contraseña invalida');

        if ((int) $usuario['intentos_fallidos'] + 1 >= self::MAX_INTENTOS_FALLIDOS) {
            $pdo = obtenerConexion();
            $stmt = $pdo->prepare('UPDATE usuarios SET bloqueado_hasta = DATE_ADD(NOW(), INTERVAL :min MINUTE) WHERE id = :id');
            $stmt->execute(['min' => self::MINUTOS_BLOQUEO, 'id' => $usuario['id']]);
            return 'Superaste el máximo de intentos. Tu cuenta quedó bloqueada por ' . self::MINUTOS_BLOQUEO . ' minutos.';
        }

        return 'Usuario o contraseña incorrectos.';
    }

    private static function iniciarSesion(array $usuario): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario_id']    = (int) $usuario['id'];
        $_SESSION['nombre']        = $usuario['nombre'];
        $_SESSION['apellido']      = $usuario['apellido'];
        $_SESSION['email']         = $usuario['email'];
        $_SESSION['perfil_id']     = (int) $usuario['perfil_id'];

        $perfil = Perfil::buscarPorId((int) $usuario['perfil_id']);
        $_SESSION['perfil_nombre'] = $perfil['nombre'] ?? '';
        $_SESSION['es_administrador'] = (int) ($perfil['es_administrador'] ?? 0);

        refrescar_permisos_en_sesion((int) $usuario['perfil_id']);
        Usuario::registrarLoginExitoso((int) $usuario['id']);
    }
}
