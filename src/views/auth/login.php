<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión · <?= h(env('APP_NAME', 'Biblioteca')) ?></title>
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<div class="pagina-auth">
    <div class="login-caja">
        <div class="login-panel">
            <div class="login-panel__icono"><?= icono('logo') ?></div>
            <h2><?= h(env('APP_NAME', 'Biblioteca')) ?></h2>
            <p>Pedí, consultá y gestioná préstamos de libros desde un mismo lugar. Ingresá con tu cuenta para continuar.</p>
        </div>
        <div class="login-form-lado">
            <div class="pagina-auth__logo">Iniciar sesión</div>
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
</div>
<script src="<?= asset_url('assets/js/toast.js') ?>"></script>
<script src="<?= asset_url('assets/js/session.js') ?>"></script>
<script>
    limpiarSesionLocal();
    <?php if ($errorGeneral): ?>
    mostrarToast('error', 'No pudimos iniciar sesión', <?= json_encode($errorGeneral, JSON_UNESCAPED_UNICODE) ?>);
    <?php endif; ?>
</script>
</body>
</html>
