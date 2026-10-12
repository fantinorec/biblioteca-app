<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña · <?= h(env('APP_NAME', 'Biblioteca')) ?></title>
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<div class="pagina-auth">
    <div class="card">
        <div class="pagina-auth__logo"><?= icono('logo') ?> <?= h(env('APP_NAME', 'Biblioteca')) ?></div>
        <div class="pagina-auth__subtitulo">Recuperar contraseña</div>

        <?php if ($enviado): ?>
            <div class="card" style="background:#e3ecdf;border-color:#bcd4b4;">
                Si el email ingresado corresponde a una cuenta activa, te enviamos un correo con las instrucciones para restablecer tu contraseña. El enlace vence en 1 hora.
            </div>
            <p class="texto-centro mt-2"><a href="login.php">Volver a iniciar sesión</a></p>
        <?php else: ?>
            <form method="post" novalidate>
                <div class="form-grupo">
                    <label for="email">Email de tu cuenta</label>
                    <input class="form-control" type="email" id="email" name="email" required autofocus>
                    <span class="form-error"><?= h($errorEmail) ?></span>
                </div>
                <button type="submit" class="btn btn-primario btn-bloque">Enviar instrucciones</button>
            </form>
            <p class="texto-centro mt-2 texto-suave"><a href="login.php">Volver a iniciar sesión</a></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
