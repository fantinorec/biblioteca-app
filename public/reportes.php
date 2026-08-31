<?php
require_once __DIR__ . '/../src/bootstrap.php';
requerir_modulo('reportes');

$pdo = obtenerConexion();

// --- Grafico 1: Prestamos por mes (ultimos 6 meses) ---------------------
$stmt = $pdo->query(
    "SELECT DATE_FORMAT(fecha_prestamo, '%Y-%m') AS periodo, COUNT(*) AS cantidad
     FROM prestamos
     WHERE fecha_prestamo >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY periodo ORDER BY periodo"
);
$prestamosPorMes = $stmt->fetchAll();

// --- Grafico 2: Libros por categoria -------------------------------------
$stmt = $pdo->query(
    "SELECT c.nombre AS categoria, COUNT(l.id) AS cantidad
     FROM categorias c
     LEFT JOIN libros l ON l.categoria_id = c.id AND l.estado = 'activo'
     WHERE c.estado = 'activo'
     GROUP BY c.id, c.nombre ORDER BY cantidad DESC"
);
$librosPorCategoria = $stmt->fetchAll();

// --- Grafico 3: Top 5 libros mas prestados -------------------------------
$stmt = $pdo->query(
    "SELECT l.titulo, COUNT(p.id) AS cantidad
     FROM prestamos p INNER JOIN libros l ON l.id = p.libro_id
     GROUP BY l.id, l.titulo ORDER BY cantidad DESC LIMIT 5"
);
$topLibros = $stmt->fetchAll();

// --- Grafico 4: Usuarios por perfil --------------------------------------
$stmt = $pdo->query(
    "SELECT p.nombre AS perfil, COUNT(u.id) AS cantidad
     FROM perfiles p LEFT JOIN usuarios u ON u.perfil_id = p.id AND u.estado = 'activo'
     WHERE p.estado = 'activo'
     GROUP BY p.id, p.nombre"
);
$usuariosPorPerfil = $stmt->fetchAll();

$tituloPagina = 'Reportes';
$claveModuloActual = 'reportes';
$scriptsExtra = ['assets/js/reportes.js'];
require __DIR__ . '/../src/partials/header.php';
?>
<div class="toolbar">
    <h2 class="mb-0">Reportes e indicadores</h2>
    <button type="button" class="btn btn-primario" id="btnExportarTodoPdf">⬇ Exportar todos los gráficos (PDF)</button>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card__titulo">
            <h3 class="mb-0">Préstamos por mes (últimos 6 meses)</h3>
            <div class="tabla-acciones">
                <button type="button" class="btn btn-outline btn-sm" data-export-xls="tablaPrestamosMes" data-nombre="prestamos_por_mes">XLS</button>
                <button type="button" class="btn btn-outline btn-sm" data-export-pdf="graficoPrestamosMes" data-nombre="prestamos_por_mes">PDF</button>
            </div>
        </div>
        <canvas id="graficoPrestamosMes" height="220"></canvas>
        <table id="tablaPrestamosMes" class="oculto">
            <thead><tr><th>Mes</th><th>Cantidad</th></tr></thead>
            <tbody>
            <?php foreach ($prestamosPorMes as $fila): ?>
                <tr><td><?= h($fila['periodo']) ?></td><td><?= (int) $fila['cantidad'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card__titulo">
            <h3 class="mb-0">Libros por categoría</h3>
            <div class="tabla-acciones">
                <button type="button" class="btn btn-outline btn-sm" data-export-xls="tablaLibrosCategoria" data-nombre="libros_por_categoria">XLS</button>
                <button type="button" class="btn btn-outline btn-sm" data-export-pdf="graficoLibrosCategoria" data-nombre="libros_por_categoria">PDF</button>
            </div>
        </div>
        <canvas id="graficoLibrosCategoria" height="220"></canvas>
        <table id="tablaLibrosCategoria" class="oculto">
            <thead><tr><th>Categoría</th><th>Cantidad</th></tr></thead>
            <tbody>
            <?php foreach ($librosPorCategoria as $fila): ?>
                <tr><td><?= h($fila['categoria']) ?></td><td><?= (int) $fila['cantidad'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card__titulo">
            <h3 class="mb-0">Top 5 libros más prestados</h3>
            <div class="tabla-acciones">
                <button type="button" class="btn btn-outline btn-sm" data-export-xls="tablaTopLibros" data-nombre="top_libros">XLS</button>
                <button type="button" class="btn btn-outline btn-sm" data-export-pdf="graficoTopLibros" data-nombre="top_libros">PDF</button>
            </div>
        </div>
        <canvas id="graficoTopLibros" height="220"></canvas>
        <table id="tablaTopLibros" class="oculto">
            <thead><tr><th>Libro</th><th>Préstamos</th></tr></thead>
            <tbody>
            <?php foreach ($topLibros as $fila): ?>
                <tr><td><?= h($fila['titulo']) ?></td><td><?= (int) $fila['cantidad'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card__titulo">
            <h3 class="mb-0">Usuarios por perfil</h3>
            <div class="tabla-acciones">
                <button type="button" class="btn btn-outline btn-sm" data-export-xls="tablaUsuariosPerfil" data-nombre="usuarios_por_perfil">XLS</button>
                <button type="button" class="btn btn-outline btn-sm" data-export-pdf="graficoUsuariosPerfil" data-nombre="usuarios_por_perfil">PDF</button>
            </div>
        </div>
        <canvas id="graficoUsuariosPerfil" height="220"></canvas>
        <table id="tablaUsuariosPerfil" class="oculto">
            <thead><tr><th>Perfil</th><th>Cantidad</th></tr></thead>
            <tbody>
            <?php foreach ($usuariosPorPerfil as $fila): ?>
                <tr><td><?= h($fila['perfil']) ?></td><td><?= (int) $fila['cantidad'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    window.DATOS_REPORTES = {
        prestamosPorMes: <?= json_encode($prestamosPorMes, JSON_UNESCAPED_UNICODE) ?>,
        librosPorCategoria: <?= json_encode($librosPorCategoria, JSON_UNESCAPED_UNICODE) ?>,
        topLibros: <?= json_encode($topLibros, JSON_UNESCAPED_UNICODE) ?>,
        usuariosPorPerfil: <?= json_encode($usuariosPorPerfil, JSON_UNESCAPED_UNICODE) ?>
    };
</script>
<?php require __DIR__ . '/../src/partials/footer.php'; ?>
