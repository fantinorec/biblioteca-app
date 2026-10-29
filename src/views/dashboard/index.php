<?php require __DIR__ . '/../../partials/header.php'; ?>
<div class="toolbar">
    <h1 class="mb-0">Hola, <?= h($_SESSION['nombre']) ?></h1>
    <a href="guia.php" class="btn btn-secundario">Guía de uso</a>
</div>
<p class="texto-suave">Resumen general del sistema de biblioteca.</p>

<?php if (!empty($ultimosLibros)): ?>
<div class="card">
    <h3 class="mb-1">Novedades:</h3>
    <div class="novedades-carrusel" id="novedadesCarrusel" data-intervalo="4000">
        <div class="novedades-carrusel__viewport">
            <div class="novedades-carrusel__pista">
                <?php foreach ($ultimosLibros as $l): ?>
                    <a class="novedades-slide" href="libros.php" title="<?= h($l['titulo']) ?>">
                        <div class="novedades-slide__imagen">
                            <?php if (!empty($l['imagen_url'])): ?>
                                <img src="<?= h($l['imagen_url']) ?>" alt="Portada de <?= h($l['titulo']) ?>"
                                     onerror='this.replaceWith(Object.assign(document.createElement("div"), {className: "libro-portada__vacia", innerHTML: <?= icono_js("book") ?>}));'>
                            <?php else: ?>
                                <div class="libro-portada__vacia"><?= icono('book') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="novedades-slide__info">
                            <span class="novedades-slide__titulo"><?= h($l['titulo']) ?></span>
                            <span class="novedades-slide__autor"><?= h($l['autor_nombre'] . ' ' . $l['autor_apellido']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if (count($ultimosLibros) > 1): ?>
            <div class="novedades-carrusel__puntos">
                <?php foreach ($ultimosLibros as $i => $l): ?>
                    <button type="button" class="novedades-carrusel__punto<?= $i === 0 ? ' activo' : '' ?>" data-indice="<?= $i ?>" aria-label="Ver <?= h($l['titulo']) ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
(function () {
    var raiz = document.getElementById('novedadesCarrusel');
    if (!raiz) return;
    var pista = raiz.querySelector('.novedades-carrusel__pista');
    var slides = raiz.querySelectorAll('.novedades-slide');
    var puntos = raiz.querySelectorAll('.novedades-carrusel__punto');
    if (slides.length <= 1) return;

    var indice = 0;
    var intervaloMs = parseInt(raiz.dataset.intervalo, 10) || 2000;
    var temporizador = null;

    function mostrar(nuevoIndice) {
        indice = (nuevoIndice + slides.length) % slides.length;
        pista.style.transform = 'translateX(-' + (indice * 100) + '%)';
        puntos.forEach(function (p, i) { p.classList.toggle('activo', i === indice); });
    }

    function reiniciarAutoplay() {
        if (temporizador) clearInterval(temporizador);
        temporizador = setInterval(function () { mostrar(indice + 1); }, intervaloMs);
    }

    puntos.forEach(function (punto) {
        punto.addEventListener('click', function () {
            mostrar(parseInt(punto.dataset.indice, 10));
            reiniciarAutoplay();
        });
    });

    // Pausamos al pasar el mouse, para que se pueda leer con calma.
    raiz.addEventListener('mouseenter', function () { if (temporizador) clearInterval(temporizador); });
    raiz.addEventListener('mouseleave', reiniciarAutoplay);

    mostrar(0);
    reiniciarAutoplay();
})();
</script>
<?php endif; ?>

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
<?php require __DIR__ . '/../../partials/footer.php'; ?>
