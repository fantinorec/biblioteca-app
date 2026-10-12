<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso denegado · <?= h(env('APP_NAME', 'Biblioteca')) ?></title>
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<div class="pagina-auth">
    <div class="card texto-centro">
        <div class="icono-circulo"><?= icono('lock') ?></div>
        <h2>Acceso denegado</h2>
        <p class="texto-suave">Tu perfil no tiene permiso para ver este módulo.</p>
        <a href="index.php" class="btn btn-primario mt-2">Volver al panel</a>
    </div>
</div>
</body>
</html>
