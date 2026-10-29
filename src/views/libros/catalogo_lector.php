<?php require __DIR__ . '/../../partials/header.php'; ?>
<div class="toolbar">
    <h2 class="mb-0">Catálogo de libros</h2>
</div>

<div class="card recomendador-ia">
    <h3 class="recomendador-ia__titulo"><?= icono('star') ?> Recomendador con IA</h3>
    <p class="texto-suave">Contanos qué tenés ganas de leer (un género, un tema, un autor parecido a...) y te sugerimos algo del catálogo.</p>
    <form id="formRecomendadorIA" class="recomendador-ia__form">
        <textarea id="pedidoRecomendacion" name="pedido" maxlength="300" rows="2"
                  placeholder="Ej: algo de misterio, corto, que no sea muy sangriento..." required></textarea>
        <button type="submit" class="btn btn-primario" id="btnPedirRecomendacion"><?= icono('star') ?> Recomendame un libro</button>
    </form>
    <div id="resultadoRecomendacionIA" class="recomendador-ia__resultado oculto"></div>
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
                    <img src="<?= h($l['imagen_url']) ?>" alt="Portada de <?= h($l['titulo']) ?>" class="catalogo-tarjeta__imagen-click"
                         onclick='verImagenAmpliada(<?= json_encode($l['imagen_url'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($l['titulo'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>)'
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

<div class="modal-fondo" id="modalImagenLibro">
    <div class="modal-caja modal-caja--imagen">
        <button type="button" class="modal-imagen-cerrar" data-cerrar-modal="modalImagenLibro" aria-label="Cerrar">&times;</button>
        <img id="imagenAmpliada" src="" alt="">
        <p id="tituloImagenAmpliada" class="texto-suave texto-centro mb-0"></p>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('formRecomendadorIA');
    var textarea = document.getElementById('pedidoRecomendacion');
    var boton = document.getElementById('btnPedirRecomendacion');
    var resultado = document.getElementById('resultadoRecomendacionIA');

    form.addEventListener('submit', function (evento) {
        evento.preventDefault();
        var pedido = textarea.value.trim();
        if (!pedido) return;

        boton.disabled = true;
        boton.textContent = 'Pensando...';
        resultado.classList.remove('oculto', 'recomendador-ia__resultado--error');
        resultado.textContent = 'Buscando algo para vos...';

        var datos = new URLSearchParams();
        datos.set('pedido', pedido);

        fetch('api/recomendar_libros.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: datos.toString(),
        })
            .then(function (respuesta) { return respuesta.json(); })
            .then(function (data) {
                resultado.textContent = data.mensaje || 'No se pudo generar la recomendación.';
                resultado.classList.toggle('recomendador-ia__resultado--error', !data.ok);
            })
            .catch(function () {
                resultado.textContent = 'No se pudo conectar con el recomendador. Probá de nuevo en un rato.';
                resultado.classList.add('recomendador-ia__resultado--error');
            })
            .finally(function () {
                boton.disabled = false;
                boton.innerHTML = <?= icono_js('star') ?> + ' Recomendame un libro';
            });
    });
})();
</script>
<script>
function verImagenAmpliada(url, titulo) {
    if (!url) return;
    document.getElementById('imagenAmpliada').src = url;
    document.getElementById('imagenAmpliada').alt = 'Portada de ' + titulo;
    document.getElementById('tituloImagenAmpliada').textContent = titulo;
    window.abrirModal('modalImagenLibro');
}
</script>
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
