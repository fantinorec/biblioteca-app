<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de cuenta · <?= h(env('APP_NAME', 'Biblioteca')) ?></title>
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<div class="pagina-auth">
    <div class="card texto-centro">
        <div class="pagina-auth__logo"><?= icono('logo') ?> <?= h(env('APP_NAME', 'Biblioteca')) ?></div>
        <div class="card" style="background:<?= $ok ? '#e3ecdf' : '#f3dede' ?>;border-color:<?= $ok ? '#bcd4b4' : '#e3b8b8' ?>;">
            <?= h($mensaje) ?>
        </div>
        <a href="login.php" class="btn btn-primario mt-2">Ir a iniciar sesión</a>
    </div>
</div>
</body>
</html>
