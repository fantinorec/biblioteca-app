<?php require __DIR__ . '/../../partials/header.php'; ?>
<div class="toolbar">
    <h2 class="mb-0">Catálogo de libros</h2>
    <?php if ($puedeCrear): ?>
        <button type="button" class="btn btn-primario" data-abrir-modal="modalLibro" onclick="prepararAltaLibro()">+ Nuevo libro</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="tabla-wrap">
        <table class="tabla">
            <thead><tr><th></th><th>Título</th><th>Autor</th><th>Categoría</th><th>Editorial</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($libros as $l): ?>
                <tr>
                    <td>
                        <?php if (!empty($l['imagen_url'])): ?>
                            <img class="libro-thumb" src="<?= h($l['imagen_url']) ?>" alt="" onerror='this.replaceWith(Object.assign(document.createElement("div"), {className: "libro-thumb libro-thumb--vacio", innerHTML: <?= icono_js("book") ?>}));'>
                        <?php else: ?>
                            <div class="libro-thumb libro-thumb--vacio"><?= icono('book') ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= h($l['titulo']) ?><?= $l['isbn'] ? '<br><span class="texto-suave" style="font-size:11.5px;">ISBN ' . h($l['isbn']) . '</span>' : '' ?></td>
                    <td><?= h($l['autor_nombre'] . ' ' . $l['autor_apellido']) ?></td>
                    <td><?= h($l['categoria_nombre']) ?></td>
                    <td><?= h($l['editorial_nombre']) ?></td>
                    <td><?= (int) $l['stock_disponible'] ?> / <?= (int) $l['stock_total'] ?></td>
                    <td><span class="badge badge-<?= $l['estado'] ?>"><?= h($l['estado']) ?></span></td>
                    <td class="tabla-acciones">
                        <?php if ($puedeEditar): ?>
                            <button type="button" class="btn btn-outline btn-sm" data-abrir-modal="modalLibro" onclick='prepararEdicionLibro(<?= json_encode($l, JSON_UNESCAPED_UNICODE) ?>)'>Editar</button>
                        <?php endif; ?>
                        <?php if ($puedeEliminar): ?>
                            <?php if ($l['estado'] === 'activo'): ?>
                                <button type="button" class="btn btn-peligro btn-sm" onclick="confirmarBajaLibro(<?= (int) $l['id'] ?>)">Baja</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-secundario btn-sm" onclick="reactivarLibro(<?= (int) $l['id'] ?>)">Reactivar</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-fondo" id="modalLibro">
    <div class="modal-caja" style="max-width:520px;">
        <h3 id="tituloModalLibro">Nuevo libro</h3>
        <form method="post" id="formLibro">
            <input type="hidden" name="accion" id="accionLibro" value="crear">
            <input type="hidden" name="id" id="idLibro">
            <div class="grid-2">
                <div class="form-grupo"><label>Título</label><input class="form-control" type="text" name="titulo" id="tituloLibro" required></div>
                <div class="form-grupo"><label>ISBN (opcional)</label><input class="form-control" type="text" name="isbn" id="isbnLibro"></div>
            </div>
            <div class="grid-2">
                <div class="form-grupo">
                    <label>Autor</label>
                    <select class="form-control" name="autor_id" id="autorLibro" required>
                        <?php foreach ($autores as $a): ?><option value="<?= (int) $a['id'] ?>"><?= h($a['nombre'] . ' ' . $a['apellido']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Categoría</label>
                    <select class="form-control" name="categoria_id" id="categoriaLibro" required>
                        <?php foreach ($categorias as $c): ?><option value="<?= (int) $c['id'] ?>"><?= h($c['nombre']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid-2">
                <div class="form-grupo">
                    <label>Editorial</label>
                    <select class="form-control" name="editorial_id" id="editorialLibro" required>
                        <?php foreach ($editoriales as $e): ?><option value="<?= (int) $e['id'] ?>"><?= h($e['nombre']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grupo"><label>Año de publicación</label><input class="form-control" type="number" name="anio_publicacion" id="anioLibro" min="1400" max="2100"></div>
            </div>
            <div class="form-grupo"><label>Stock total (cantidad de ejemplares)</label><input class="form-control" type="number" name="stock_total" id="stockLibro" min="1" value="1" required></div>
            <div class="form-grupo">
                <label>URL de la portada (opcional)</label>
                <input class="form-control" type="text" name="imagen_url" id="imagenLibro" placeholder="https://...">
                <span class="form-ayuda">Pegá el link de una imagen de la tapa del libro. Se va a mostrar en el catálogo y en el panel principal.</span>
            </div>
            <div class="modal-acciones">
                <button type="button" class="btn btn-secundario" data-cerrar-modal="modalLibro">Cancelar</button>
                <button type="submit" class="btn btn-primario">Guardar</button>
            </div>
        </form>
    </div>
</div>

<form method="post" id="formAccionLibro" class="oculto">
    <input type="hidden" name="accion" id="accionRapidaLibro">
    <input type="hidden" name="id" id="idAccionRapidaLibro">
</form>

<script>
function prepararAltaLibro() {
    document.getElementById('tituloModalLibro').textContent = 'Nuevo libro';
    document.getElementById('accionLibro').value = 'crear';
    document.getElementById('formLibro').reset();
    document.getElementById('idLibro').value = '';
}
function prepararEdicionLibro(l) {
    document.getElementById('tituloModalLibro').textContent = 'Editar libro';
    document.getElementById('accionLibro').value = 'editar';
    document.getElementById('idLibro').value = l.id;
    document.getElementById('tituloLibro').value = l.titulo;
    document.getElementById('isbnLibro').value = l.isbn || '';
    document.getElementById('autorLibro').value = l.autor_id;
    document.getElementById('categoriaLibro').value = l.categoria_id;
    document.getElementById('editorialLibro').value = l.editorial_id;
    document.getElementById('anioLibro').value = l.anio_publicacion || '';
    document.getElementById('stockLibro').value = l.stock_total;
    document.getElementById('imagenLibro').value = l.imagen_url || '';
}
function confirmarBajaLibro(id) {
    confirmarAccion('Dar de baja libro', '¿Seguro que querés dar de baja este libro?', function () {
        document.getElementById('accionRapidaLibro').value = 'baja';
        document.getElementById('idAccionRapidaLibro').value = id;
        document.getElementById('formAccionLibro').submit();
    });
}
function reactivarLibro(id) {
    document.getElementById('accionRapidaLibro').value = 'reactivar';
    document.getElementById('idAccionRapidaLibro').value = id;
    document.getElementById('formAccionLibro').submit();
}
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
