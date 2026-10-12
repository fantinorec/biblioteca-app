<?php

$tituloPagina = $tituloPagina ?? 'Biblioteca';
$claveModuloActual = $claveModuloActual ?? '';
$modulos = modulos_visibles_usuario();
$nombreCompleto = h(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''));
$iniciales = strtoupper(mb_substr($_SESSION['nombre'] ?? '?', 0, 1) . mb_substr($_SESSION['apellido'] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($tituloPagina) ?> · <?= h(env('APP_NAME', 'Biblioteca')) ?></title>
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <div class="topbar__marca">
            <button type="button" id="botonMenu" class="topbar__toggle" aria-label="Abrir menu">&#9776;</button>
            <?= icono('logo', 'topbar__marca-icono') ?>
            <span><?= h(env('APP_NAME', 'Biblioteca')) ?></span>
        </div>
        <div class="topbar__usuario">
            <div class="avatar" id="avatarUsuarioActual"><?= h($iniciales) ?></div>
            <span id="nombreUsuarioActual"><?= $nombreCompleto ?> · <?= h($_SESSION['perfil_nombre'] ?? '') ?></span>
            <form action="logout.php" method="post" style="margin:0;">
                <button type="submit" class="btn-logout">Salir</button>
            </form>
        </div>
    </header>

    <aside class="sidebar" id="sidebarPrincipal">
        <div class="sidebar__etiqueta">Menú</div>
        <nav>
            <?php foreach ($modulos as $modulo): ?>
                <a href="<?= h($modulo['ruta']) ?>" class="<?= $claveModuloActual === $modulo['clave'] ? 'activo' : '' ?>">
                    <span class="sidebar__icono"><?= icono($modulo['icono']) ?></span>
                    <?= h($modulo['nombre']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="contenido">
