<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/models/Usuario.php';

if (usuario_autenticado()) {
    redirigir('index.php');
}

const PERFIL_LECTOR_ID = 2;

$errores = [];
$viejo = ['nombre' => '', 'apellido' => '', 'email' => ''];
$registroOk = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $viejo['nombre'] = trim($_POST['nombre'] ?? '');
    $viejo['apellido'] = trim($_POST['apellido'] ?? '');
    $viejo['email'] = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $passwordConfirmacion = $_POST['password_confirmacion'] ?? '';

    // 1) Campos requeridos
    $errores = validar_campos_requeridos($_POST, [
        'nombre'   => 'Nombre',
        'apellido' => 'Apellido',
        'email'    => 'Email',
        'password' => 'Contraseña',
        'password_confirmacion' => 'Confirmación de contraseña',
    ]);

    // 2) Formato de email
    if (empty($errores['email']) && !validar_email($viejo['email'])) {
        $errores['email'] = 'El formato del email no es valido.';
    }

    // 3) Unicidad de email
    if (empty($errores['email']) && Usuario::existeEmail($viejo['email'])) {
        $errores['email'] = 'Ya existe una cuenta registrada con ese email.';
    }

    // 4) Politica de contraseña
    if (empty($errores['password']) && !validar_password($password)) {
        $errores['password'] = mensaje_politica_password();
    }

    // 5) Coincidencia de contraseñas
    if (empty($errores['password']) && $password !== $passwordConfirmacion) {
        $errores['password_confirmacion'] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        $token = generar_token();
        $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $idNuevo = Usuario::crear([
            'nombre'                    => $viejo['nombre'],
            'apellido'                  => $viejo['apellido'],
            'email'                     => $viejo['email'],
            'password_hash'             => password_hash($password, PASSWORD_BCRYPT),
            'perfil_id'                 => PERFIL_LECTOR_ID,
            'email_verificado'          => 0,
            'token_verificacion'        => $token,
            'token_verificacion_expira' => $expira,
            'estado'                    => 'activo',
        ]);

        $envioOk = enviar_email_verificacion($viejo['email'], $viejo['nombre'], $token);
        registrar_auditoria($idNuevo, $viejo['email'], 'CREAR', 'usuarios', $idNuevo, 'Alta por autoregistro');

        if (!$envioOk) {
            $errores['general'] = 'La cuenta se creó, pero no pudimos enviar el correo de verificación. Revisa la configuración SMTP del archivo .env o pedile a un administrador que verifique tu cuenta manualmente.';
        } else {
            $registroOk = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta · <?= h(env('APP_NAME', 'Biblioteca')) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="pagina-auth">
    <div class="card">
        <div class="pagina-auth__logo">📚 <?= h(env('APP_NAME', 'Biblioteca')) ?></div>
        <div class="pagina-auth__subtitulo">Crear una cuenta nueva</div>

        <?php if ($registroOk): ?>
            <div class="card" style="background:#e3ecdf;border-color:#bcd4b4;">
                <strong>¡Cuenta creada!</strong>
                <p class="mb-0">Te enviamos un correo a <?= h($viejo['email']) ?> para confirmar tu cuenta. Revisa tu bandeja de entrada (y spam) antes de iniciar sesión.</p>
            </div>
            <p class="texto-centro mt-2"><a href="login.php">Ir a iniciar sesión</a></p>
        <?php else: ?>
            <?php if (!empty($errores['general'])): ?>
                <div class="card" style="background:#fdeecb;border-color:#e9d191;"><?= h($errores['general']) ?></div>
            <?php endif; ?>
            <form method="post" id="formRegistro" novalidate>
                <div class="form-grupo">
                    <label for="nombre">Nombre</label>
                    <input class="form-control" type="text" id="nombre" name="nombre" value="<?= h($viejo['nombre']) ?>" required>
                    <span class="form-error"><?= h($errores['nombre'] ?? '') ?></span>
                </div>
                <div class="form-grupo">
                    <label for="apellido">Apellido</label>
                    <input class="form-control" type="text" id="apellido" name="apellido" value="<?= h($viejo['apellido']) ?>" required>
                    <span class="form-error"><?= h($errores['apellido'] ?? '') ?></span>
                </div>
                <div class="form-grupo">
                    <label for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" data-validar="email" value="<?= h($viejo['email']) ?>" required>
                    <span class="form-error"><?= h($errores['email'] ?? '') ?></span>
                </div>
                <div class="form-grupo">
                    <label for="password">Contraseña</label>
                    <input class="form-control" type="password" id="password" name="password" data-validar="password" required>
                    <span class="form-ayuda"><?= h(mensaje_politica_password()) ?></span>
                    <span class="form-error"><?= h($errores['password'] ?? '') ?></span>
                </div>
                <div class="form-grupo">
                    <label for="password_confirmacion">Confirmar contraseña</label>
                    <input class="form-control" type="password" id="password_confirmacion" name="password_confirmacion" data-validar="password-confirmacion" required>
                    <span class="form-error"><?= h($errores['password_confirmacion'] ?? '') ?></span>
                </div>
                <button type="submit" class="btn btn-primario btn-bloque">Crear cuenta</button>
            </form>
            <p class="texto-centro mt-2 texto-suave">¿Ya tenés cuenta? <a href="login.php">Iniciar sesión</a></p>
        <?php endif; ?>
    </div>
</div>
<script src="assets/js/toast.js"></script>
<script src="assets/js/validaciones.js"></script>
<script>activarValidacionFormulario('formRegistro');</script>
</body>
</html>
