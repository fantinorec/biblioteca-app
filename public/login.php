<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/models/Usuario.php';
require_once __DIR__ . '/../src/models/Perfil.php';

if (usuario_autenticado()) {
    redirigir('index.php');
}

const MAX_INTENTOS_FALLIDOS = 5;
const MINUTOS_BLOQUEO = 15;

$errorGeneral = '';
$emailViejo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $emailViejo = $email;

    $errores = validar_campos_requeridos($_POST, ['email' => 'Email', 'password' => 'Contraseña']);

    if (empty($errores)) {
        $usuario = Usuario::buscarPorEmail($email);

        if (!$usuario) {
            $errorGeneral = 'Usuario o contraseña incorrectos.';
            registrar_auditoria(null, $email, 'LOGIN_FALLIDO', 'usuarios', null, 'Email no registrado');
        } elseif ($usuario['estado'] !== 'activo') {
            $errorGeneral = 'Esta cuenta se encuentra dada de baja. Contacta a un administrador.';
        } elseif (!empty($usuario['bloqueado_hasta']) && strtotime($usuario['bloqueado_hasta']) > time()) {
            $errorGeneral = 'Tu cuenta esta bloqueada temporalmente por intentos fallidos. Probá de nuevo en unos minutos.';
        } elseif ((int) $usuario['email_verificado'] === 0) {
            $errorGeneral = 'Todavia no confirmaste tu email. Revisa tu casilla de correo para activar la cuenta.';
        } elseif (!password_verify($password, $usuario['password_hash'])) {
            Usuario::registrarLoginFallido((int) $usuario['id']);
            registrar_auditoria((int) $usuario['id'], $email, 'LOGIN_FALLIDO', 'usuarios', (int) $usuario['id'], 'Contraseña invalida');

            if ((int) $usuario['intentos_fallidos'] + 1 >= MAX_INTENTOS_FALLIDOS) {
                $pdo = obtenerConexion();
                $stmt = $pdo->prepare('UPDATE usuarios SET bloqueado_hasta = DATE_ADD(NOW(), INTERVAL :min MINUTE) WHERE id = :id');
                $stmt->execute(['min' => MINUTOS_BLOQUEO, 'id' => $usuario['id']]);
                $errorGeneral = 'Superaste el máximo de intentos. Tu cuenta quedó bloqueada por ' . MINUTOS_BLOQUEO . ' minutos.';
            } else {
                $errorGeneral = 'Usuario o contraseña incorrectos.';
            }
        } else {
            // Login exitoso
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
            registrar_auditoria((int) $usuario['id'], $email, 'LOGIN', 'usuarios', (int) $usuario['id'], 'Inicio de sesion exitoso');

            redirigir('index.php');
        }
    } else {
        $errorGeneral = 'Completa tu email y contraseña.';
    }
}

$motivo = $_GET['motivo'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión · <?= h(env('APP_NAME', 'Biblioteca')) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="pagina-auth">
    <div class="card">
        <div class="pagina-auth__logo">📚 <?= h(env('APP_NAME', 'Biblioteca')) ?></div>
        <div class="pagina-auth__subtitulo">Ingresá con tu cuenta</div>

        <?php if ($motivo === 'sin_sesion'): ?>
            <div id="avisoSinSesion" class="card" style="background:#fdeecb;border-color:#e9d191;">Tu sesión expiró o no iniciaste sesión. Ingresá de nuevo.</div>
        <?php endif; ?>

        <form method="post" id="formLogin" novalidate>
            <div class="form-grupo">
                <label for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= h($emailViejo) ?>" required autofocus>
            </div>
            <div class="form-grupo">
                <label for="password">Contraseña</label>
                <input class="form-control" type="password" id="password" name="password" required>
                <span class="form-error" id="errorLogin"><?= h($errorGeneral) ?></span>
            </div>
            <button type="submit" class="btn btn-primario btn-bloque">Ingresar</button>
        </form>
        <p class="texto-centro mt-2"><a href="recuperar_password.php">¿Olvidaste tu contraseña?</a></p>
        <p class="texto-centro texto-suave">¿No tenés cuenta? <a href="registro.php">Registrate</a></p>
    </div>
</div>
<script src="assets/js/toast.js"></script>
<script src="assets/js/session.js"></script>
<script>
    limpiarSesionLocal();
    <?php if ($errorGeneral): ?>
    mostrarToast('error', 'No pudimos iniciar sesión', <?= json_encode($errorGeneral, JSON_UNESCAPED_UNICODE) ?>);
    <?php endif; ?>
</script>
</body>
</html>
