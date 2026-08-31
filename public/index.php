<?php
require_once __DIR__ . '/../src/bootstrap.php';
requerir_modulo('dashboard');
require_once __DIR__ . '/../src/models/Prestamo.php';

Prestamo::actualizarVencidos();

$pdo = obtenerConexion();
$totalLibros = (int) $pdo->query('SELECT COUNT(*) FROM libros WHERE estado = "activo"')->fetchColumn();
$totalUsuarios = (int) $pdo->query('SELECT COUNT(*) FROM usuarios WHERE estado = "activo"')->fetchColumn();
$prestamosActivos = (int) $pdo->query('SELECT COUNT(*) FROM prestamos WHERE estado = "prestado"')->fetchColumn();
$prestamosVencidos = (int) $pdo->query('SELECT COUNT(*) FROM prestamos WHERE estado = "vencido"')->fetchColumn();

$tituloPagina = 'Dashboard';
$claveModuloActual = 'dashboard';
require __DIR__ . '/../src/partials/header.php';
?>
<h1>Hola, <?= h($_SESSION['nombre']) ?> 👋</h1>
<p class="texto-suave">Resumen general del sistema de biblioteca.</p>

<div class="grid-stats">
    <div class="stat-tile">
        <div class="stat-tile__valor"><?= $totalLibros ?></div>
        <div class="stat-tile__etiqueta">Libros activos</div>
    </div>
    <div class="stat-tile">
        <div class="stat-tile__valor"><?= $totalUsuarios ?></div>
        <div class="stat-tile__etiqueta">Usuarios activos</div>
    </div>
    <div class="stat-tile">
        <div class="stat-tile__valor"><?= $prestamosActivos ?></div>
        <div class="stat-tile__etiqueta">Préstamos vigentes</div>
    </div>
    <div class="stat-tile">
        <div class="stat-tile__valor"><?= $prestamosVencidos ?></div>
        <div class="stat-tile__etiqueta">Préstamos vencidos</div>
    </div>
</div>

<div class="card">
    <h3>¿Qué podés hacer desde acá?</h3>
    <p class="texto-suave mb-0">Usá el menú de la izquierda para administrar usuarios y perfiles, el catálogo de libros, los préstamos y ver los reportes con gráficos. Para el detalle de indicadores con gráficos y exportación a Excel/PDF, entrá a <a href="reportes.php">Reportes</a>.</p>
</div>
<?php require __DIR__ . '/../src/partials/footer.php'; ?>
