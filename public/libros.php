<?php
require_once __DIR__ . '/../src/bootstrap.php';
requerir_modulo('libros');
require_once __DIR__ . '/../src/models/Libro.php';
require_once __DIR__ . '/../src/models/TablaMaestra.php';

$puedeCrear = tiene_permiso('libros', 'crear');
$puedeEditar = tiene_permiso('libros', 'editar');
$puedeEliminar = tiene_permiso('libros', 'eliminar');

function volverLibros(bool $ok, string $mensaje): never
{
    redirigir('libros.php?' . ($ok ? 'ok' : 'error') . '=1&msg=' . urlencode($mensaje));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if (in_array($accion, ['crear', 'editar'], true)) {
        if (!($accion === 'crear' ? $puedeCrear : $puedeEditar)) {
            volverLibros(false, 'No tenés permiso para esta acción.');
        }

        $datos = [
            'titulo'            => trim($_POST['titulo'] ?? ''),
            'isbn'              => trim($_POST['isbn'] ?? ''),
            'autor_id'          => (int) ($_POST['autor_id'] ?? 0),
            'categoria_id'      => (int) ($_POST['categoria_id'] ?? 0),
            'editorial_id'      => (int) ($_POST['editorial_id'] ?? 0),
            'anio_publicacion'  => trim($_POST['anio_publicacion'] ?? ''),
            'stock_total'       => (int) ($_POST['stock_total'] ?? 1),
        ];

        $errores = validar_campos_requeridos($datos, [
            'titulo' => 'Título', 'autor_id' => 'Autor', 'categoria_id' => 'Categoría', 'editorial_id' => 'Editorial',
        ]);
        if ($datos['stock_total'] < 1) {
            $errores['stock_total'] = 'El stock debe ser al menos 1.';
        }
        if (empty($errores) && $datos['isbn'] !== '' && Libro::existeIsbn($datos['isbn'], $accion === 'editar' ? $id : null)) {
            $errores['isbn'] = 'Ya existe un libro con ese ISBN.';
        }
        if (!empty($errores)) {
            volverLibros(false, 'Revisá los datos: ' . implode(' ', $errores));
        }

        if ($accion === 'crear') {
            $nuevoId = Libro::crear($datos);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'CREAR', 'libros', $nuevoId, "Alta de libro \"{$datos['titulo']}\"");
            volverLibros(true, 'Libro creado correctamente.');
        } else {
            Libro::actualizar($id, $datos);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', 'libros', $id, "Edición de libro #$id");
            volverLibros(true, 'Libro actualizado correctamente.');
        }
    }

    if ($accion === 'baja' && $puedeEliminar) {
        Libro::darBaja($id);
        registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'BAJA', 'libros', $id, "Baja lógica de libro #$id");
        volverLibros(true, 'Libro dado de baja.');
    }

    if ($accion === 'reactivar' && $puedeEliminar) {
        Libro::reactivar($id);
        registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', 'libros', $id, "Reactivación de libro #$id");
        volverLibros(true, 'Libro reactivado.');
    }

    volverLibros(false, 'Acción no permitida.');
}

$libros = Libro::listar();
$autores = TablaMaestra::listar('autores', 'activo');
$categorias = TablaMaestra::listar('categorias', 'activo');
$editoriales = TablaMaestra::listar('editoriales', 'activo');

$tituloPagina = 'Libros';
$claveModuloActual = 'libros';
require __DIR__ . '/../src/partials/header.php';
?>
<div class="toolbar">
    <h2 class="mb-0">Catálogo de libros</h2>
    <?php if ($puedeCrear): ?>
        <button type="button" class="btn btn-primario" data-abrir-modal="modalLibro" onclick="prepararAltaLibro()">+ Nuevo libro</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="tabla-wrap">
        <table class="tabla">
            <thead><tr><th>Título</th><th>Autor</th><th>Categoría</th><th>Editorial</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($libros as $l): ?>
                <tr>
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
<?php require __DIR__ . '/../src/partials/footer.php'; ?>
