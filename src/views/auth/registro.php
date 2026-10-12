<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta · <?= h(env('APP_NAME', 'Biblioteca')) ?></title>
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<div class="pagina-auth">
    <div class="card">
        <div class="pagina-auth__logo"><?= icono('logo') ?> <?= h(env('APP_NAME', 'Biblioteca')) ?></div>
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
<script src="<?= asset_url('assets/js/toast.js') ?>"></script>
<script src="<?= asset_url('assets/js/validaciones.js') ?>"></script>
<script>activarValidacionFormulario('formRegistro');</script>
</body>
</html>
