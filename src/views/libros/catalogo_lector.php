<?php require __DIR__ . '/../../partials/header.php'; ?>
<div class="toolbar">
    <h2 class="mb-0">Catálogo de libros</h2>
</div>

<div class="filtro-catalogo">
    <div class="filtro-catalogo__botones">
        <button type="button" class="chip-filtro-toggle" data-toggle-filtro="categorias">
            <?= icono('tag') ?> Categorías
        </button>
        <button type="button" class="chip-filtro-toggle" data-toggle-filtro="autores">
            <?= icono('pen') ?> Autores
        </button>
        <button type="button" class="chip-filtro-limpiar oculto" id="btnLimpiarFiltro">Ver todo el catálogo</button>
    </div>

    <div class="filtro-catalogo__chips oculto" id="chipsCategorias">
        <?php if (empty($categorias)): ?>
            <span class="texto-suave">No hay categorías cargadas.</span>
        <?php endif; ?>
        <?php foreach ($categorias as $c): ?>
            <button type="button" class="chip-filtro" data-filtro-tipo="categoria" data-filtro-valor="<?= (int) $c['id'] ?>"><?= h($c['nombre']) ?></button>
        <?php endforeach; ?>
    </div>
    <div class="filtro-catalogo__chips oculto" id="chipsAutores">
        <?php if (empty($autores)): ?>
            <span class="texto-suave">No hay autores cargados.</span>
        <?php endif; ?>
        <?php foreach ($autores as $a): ?>
            <button type="button" class="chip-filtro" data-filtro-tipo="autor" data-filtro-valor="<?= (int) $a['id'] ?>"><?= h($a['nombre'] . ' ' . $a['apellido']) ?></button>
        <?php endforeach; ?>
    </div>

    <p class="texto-suave mt-1" id="etiquetaFiltroActivo"></p>
</div>

<div class="catalogo-grid" id="catalogoGrid">
    <?php foreach ($libros as $l): ?>
        <div class="catalogo-tarjeta" data-categoria-id="<?= (int) $l['categoria_id'] ?>" data-autor-id="<?= (int) $l['autor_id'] ?>">
            <div class="catalogo-tarjeta__imagen">
                <?php if (!empty($l['imagen_url'])): ?>
                    <img src="<?= h($l['imagen_url']) ?>" alt="Portada de <?= h($l['titulo']) ?>"
                         onerror='this.replaceWith(Object.assign(document.createElement("div"), {className: "catalogo-tarjeta__imagen-vacia", innerHTML: <?= icono_js("book") ?>}));'>
                <?php else: ?>
                    <div class="catalogo-tarjeta__imagen-vacia"><?= icono('book') ?></div>
                <?php endif; ?>
            </div>
            <div class="catalogo-tarjeta__cuerpo">
                <h3 class="catalogo-tarjeta__titulo"><?= h($l['titulo']) ?></h3>
                <p class="catalogo-tarjeta__meta">
                    <?= h($l['autor_nombre'] . ' ' . $l['autor_apellido']) ?> · <?= h($l['categoria_nombre']) ?><?= $l['anio_publicacion'] ? ' · ' . (int) $l['anio_publicacion'] : '' ?>
                </p>
                <p class="catalogo-tarjeta__editorial texto-suave">Editorial <?= h($l['editorial_nombre']) ?></p>
                <div class="catalogo-tarjeta__pie">
                    <?php if ((int) $l['stock_disponible'] > 0): ?>
                        <span class="badge badge-disponible">Disponible</span>
                        <?php if ($puedeCrearPrestamo): ?>
                            <a href="prestamos.php?libro_id=<?= (int) $l['id'] ?>" class="catalogo-tarjeta__accion"><?= icono('exchange') ?> Pedir préstamo</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge badge-agotado">Sin stock</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php if (empty($libros)): ?>
    <div class="card texto-centro texto-suave">Todavía no hay libros cargados en el catálogo.</div>
<?php endif; ?>

<script>
(function () {
    var botonesToggle = document.querySelectorAll('[data-toggle-filtro]');
    var panelCategorias = document.getElementById('chipsCategorias');
    var panelAutores = document.getElementById('chipsAutores');
    var btnLimpiar = document.getElementById('btnLimpiarFiltro');
    var etiqueta = document.getElementById('etiquetaFiltroActivo');
    var tarjetas = document.querySelectorAll('.catalogo-tarjeta');
    var paneles = { categorias: panelCategorias, autores: panelAutores };

    botonesToggle.forEach(function (boton) {
        boton.addEventListener('click', function () {
            var clave = boton.getAttribute('data-toggle-filtro');
            var panel = paneles[clave];
            var estabaAbierto = !panel.classList.contains('oculto');
            Object.keys(paneles).forEach(function (k) { paneles[k].classList.add('oculto'); });
            if (!estabaAbierto) panel.classList.remove('oculto');
        });
    });

    document.querySelectorAll('.chip-filtro').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var tipo = chip.getAttribute('data-filtro-tipo');
            var valor = chip.getAttribute('data-filtro-valor');

            document.querySelectorAll('.chip-filtro').forEach(function (c) { c.classList.remove('activo'); });
            chip.classList.add('activo');

            tarjetas.forEach(function (tarjeta) {
                var coincide = tipo === 'categoria'
                    ? tarjeta.getAttribute('data-categoria-id') === valor
                    : tarjeta.getAttribute('data-autor-id') === valor;
                tarjeta.classList.toggle('oculto', !coincide);
            });

            etiqueta.textContent = 'Mostrando: ' + chip.textContent.trim();
            btnLimpiar.classList.remove('oculto');
            panelCategorias.classList.add('oculto');
            panelAutores.classList.add('oculto');
        });
    });

    btnLimpiar.addEventListener('click', function () {
        document.querySelectorAll('.chip-filtro').forEach(function (c) { c.classList.remove('activo'); });
        tarjetas.forEach(function (tarjeta) { tarjeta.classList.remove('oculto'); });
        etiqueta.textContent = '';
        btnLimpiar.classList.add('oculto');
    });
})();
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
