<?php
$colspanTabla = 5 + ($puedeGestionarTodos ? 2 : 0);
?>
<?php require __DIR__ . '/../../partials/header.php'; ?>
<div class="toolbar">
    <h2 class="mb-0"><?= $puedeGestionarTodos ? 'Préstamos de la biblioteca' : 'Mis préstamos' ?></h2>
    <?php if ($puedeCrear): ?>
        <button type="button" class="btn btn-primario" data-abrir-modal="modalPrestamo">
            <?= $puedeGestionarTodos ? '+ Nuevo préstamo' : '+ Solicitar préstamo' ?>
        </button>
    <?php endif; ?>
</div>

<?php if (!$puedeGestionarTodos): ?>
    <p class="texto-suave mb-2">Tus solicitudes quedan <span class="badge badge-pendiente">pendiente</span> hasta que un bibliotecario o administrador las revise.</p>
<?php endif; ?>

<?php if ($puedeGestionarTodos): ?>
    <div class="card">
        <form method="get">
            <div class="form-grupo" style="margin-bottom:0;">
                <label>Buscar por nombre, apellido o email del lector</label>
                <div style="display:flex; gap:8px;">
                    <input class="form-control" type="text" name="buscar" value="<?= h($busqueda) ?>" placeholder="ej: Ana Gómez o ana@correo.com">
                    <button type="submit" class="btn btn-primario">Buscar</button>
                    <?php if ($busqueda !== ''): ?>
                        <a href="prestamos.php" class="btn btn-secundario">Limpiar</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="card">
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
            <tr>
                <th>Libro</th>
                <?php if ($puedeGestionarTodos): ?><th>Lector</th><?php endif; ?>
                <th>Fecha solicitud</th><th>Devolución estimada</th><th>Devolución real</th><th>Estado</th>
                <?php if ($puedeGestionarTodos): ?><th>Acciones</th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($prestamos as $p): ?>
                <tr>
                    <td><?= h($p['libro_titulo']) ?></td>
                    <?php if ($puedeGestionarTodos): ?><td><?= h($p['usuario_nombre'] . ' ' . $p['usuario_apellido']) ?></td><?php endif; ?>
                    <td><?= h($p['fecha_prestamo']) ?></td>
                    <td><?= h($p['fecha_devolucion_estimada']) ?></td>
                    <td><?= $p['fecha_devolucion_real'] ? h($p['fecha_devolucion_real']) : '—' ?></td>
                    <td><span class="badge badge-<?= $p['estado'] ?>"><?= h($p['estado']) ?></span></td>
                    <?php if ($puedeGestionarTodos): ?>
                        <td class="tabla-acciones">
                            <?php if ($p['estado'] === 'pendiente'): ?>
                                <button type="button" class="btn btn-primario btn-sm" onclick="confirmarAprobacion(<?= (int) $p['id'] ?>)">Aprobar</button>
                                <button type="button" class="btn btn-peligro btn-sm" onclick="confirmarRechazo(<?= (int) $p['id'] ?>)">Rechazar</button>
                            <?php elseif ($p['estado'] === 'prestado'): ?>
                                <button type="button" class="btn btn-outline btn-sm" onclick="confirmarDevolucion(<?= (int) $p['id'] ?>)">Registrar devolución</button>
                                <button type="button" class="btn btn-secundario btn-sm" onclick="confirmarCancelacion(<?= (int) $p['id'] ?>, 'aprobacion')" title="Deshacer la aprobación por si fue un error">Cancelar</button>
                            <?php elseif ($p['estado'] === 'vencido'): ?>
                                <button type="button" class="btn btn-outline btn-sm" onclick="confirmarDevolucion(<?= (int) $p['id'] ?>)">Registrar devolución</button>
                            <?php elseif ($p['estado'] === 'rechazado'): ?>
                                <button type="button" class="btn btn-secundario btn-sm" onclick="confirmarCancelacion(<?= (int) $p['id'] ?>, 'rechazo')" title="Deshacer el rechazo por si fue un error">Cancelar</button>
                            <?php else: ?>
                                <span class="texto-suave">—</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($prestamos)): ?>
                <tr><td colspan="<?= $colspanTabla ?>" class="texto-suave">
                    <?= $busqueda !== ''
                        ? 'No se encontraron préstamos de ningún lector que coincida con "' . h($busqueda) . '".'
                        : 'No hay préstamos para mostrar.' ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-fondo" id="modalPrestamo">
    <div class="modal-caja">
        <h3><?= $puedeGestionarTodos ? 'Nuevo préstamo' : 'Solicitar préstamo' ?></h3>
        <form method="post">
            <input type="hidden" name="accion" value="crear">
            <?php if ($puedeGestionarTodos): ?>
                <div class="form-grupo">
                    <label>Lector</label>
                    <input class="form-control" type="text" id="lectorBusqueda" list="listaLectores" autocomplete="off" placeholder="Buscá por nombre, apellido o email">
                    <datalist id="listaLectores">
                        <?php foreach ($usuariosActivos as $u): ?>
                            <option data-id="<?= (int) $u['id'] ?>" value="<?= h($u['nombre'] . ' ' . $u['apellido'] . ' (' . $u['email'] . ')') ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <input type="hidden" name="usuario_id" id="lectorId">
                </div>
            <?php endif; ?>
            <div class="form-grupo">
                <label>Libro (solo se listan los que tienen stock disponible)</label>
                <select class="form-control" name="libro_id" required>
                    <?php foreach ($librosDisponibles as $l): ?>
                        <option value="<?= (int) $l['id'] ?>"><?= h($l['titulo']) ?> (<?= (int) $l['stock_disponible'] ?> disponibles)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <p class="texto-suave" style="margin-top:-4px;">El plazo de devolución es fijo: 3 meses desde el día en que se presta el libro.</p>
            <div class="form-grupo">
                <label>Observaciones (opcional)</label>
                <textarea class="form-control" name="observaciones" rows="2"></textarea>
            </div>
            <div class="modal-acciones">
                <button type="button" class="btn btn-secundario" data-cerrar-modal="modalPrestamo">Cancelar</button>
                <button type="submit" class="btn btn-primario"><?= $puedeGestionarTodos ? 'Registrar préstamo' : 'Enviar solicitud' ?></button>
            </div>
        </form>
    </div>
</div>

<form method="post" id="formDevolucion" class="oculto">
    <input type="hidden" name="accion" value="devolver">
    <input type="hidden" name="id" id="idDevolucion">
</form>
<form method="post" id="formAprobacion" class="oculto">
    <input type="hidden" name="accion" value="aprobar">
    <input type="hidden" name="id" id="idAprobacion">
</form>
<form method="post" id="formRechazo" class="oculto">
    <input type="hidden" name="accion" value="rechazar">
    <input type="hidden" name="id" id="idRechazo">
</form>
<form method="post" id="formCancelacion" class="oculto">
    <input type="hidden" name="accion" value="cancelar">
    <input type="hidden" name="id" id="idCancelacion">
</form>

<script>
function confirmarDevolucion(id) {
    confirmarAccion('Registrar devolución', '¿Confirmás la devolución de este libro? Se repondrá el stock automáticamente.', function () {
        document.getElementById('idDevolucion').value = id;
        document.getElementById('formDevolucion').submit();
    });
}
function confirmarAprobacion(id) {
    confirmarAccion('Aprobar solicitud', '¿Confirmás que se presta este libro? Se descontará el stock disponible.', function () {
        document.getElementById('idAprobacion').value = id;
        document.getElementById('formAprobacion').submit();
    });
}
function confirmarRechazo(id) {
    confirmarAccion('Rechazar solicitud', '¿Seguro que querés rechazar esta solicitud de préstamo?', function () {
        document.getElementById('idRechazo').value = id;
        document.getElementById('formRechazo').submit();
    });
}
function confirmarCancelacion(id, tipo) {
    var mensaje = tipo === 'aprobacion'
        ? '¿Seguro que querés cancelar esta aprobación? El libro se repone al stock y la solicitud vuelve a quedar pendiente para revisarla de nuevo.'
        : '¿Seguro que querés cancelar este rechazo? La solicitud vuelve a quedar pendiente para revisarla de nuevo.';
    confirmarAccion('Cancelar decisión', mensaje, function () {
        document.getElementById('idCancelacion').value = id;
        document.getElementById('formCancelacion').submit();
    });
}

// Si llegamos desde "Pedir préstamo" en el catálogo (?libro_id=X), abrimos el
// modal de alta con ese libro ya seleccionado.
(function () {
    var params = new URLSearchParams(window.location.search);
    var libroId = params.get('libro_id');
    if (!libroId) return;
    var select = document.querySelector('#modalPrestamo select[name="libro_id"]');
    if (select && select.querySelector('option[value="' + libroId + '"]')) {
        select.value = libroId;
    }
    if (typeof abrirModal === 'function') abrirModal('modalPrestamo');
})();

// Campo "Lector" con búsqueda por nombre, apellido o email (igual que el
// buscador de la tabla): el usuario escribe y elige una sugerencia de la
// datalist, y de ahí sacamos el id real que viaja en un input oculto.
(function () {
    var inputLector = document.getElementById('lectorBusqueda');
    var listaLectores = document.getElementById('listaLectores');
    var idLector = document.getElementById('lectorId');
    if (!inputLector || !listaLectores || !idLector) return;

    function opcionElegida() {
        var opciones = listaLectores.querySelectorAll('option');
        for (var i = 0; i < opciones.length; i++) {
            if (opciones[i].value === inputLector.value) {
                return opciones[i];
            }
        }
        return null;
    }

    function sincronizarLector() {
        var opcion = opcionElegida();
        idLector.value = opcion ? opcion.getAttribute('data-id') : '';
    }

    inputLector.addEventListener('input', sincronizarLector);

    var formPrestamo = inputLector.closest('form');
    if (formPrestamo) {
        formPrestamo.addEventListener('submit', function (evento) {
            if (!idLector.value) {
                evento.preventDefault();
                mostrarToast('error', 'Revisá el formulario', 'Elegí un lector de la lista de sugerencias (podés buscar por nombre, apellido o email) antes de registrar el préstamo.');
            }
        });
    }

    // Si se abre el modal de nuevo tras un intento fallido, limpiamos la
    // búsqueda anterior para no dejar un lector "elegido" a medias.
    var botonAbrir = document.querySelector('[data-abrir-modal="modalPrestamo"]');
    if (botonAbrir) {
        botonAbrir.addEventListener('click', function () {
            if (!opcionElegida()) {
                inputLector.value = '';
                idLector.value = '';
            }
        });
    }
})();
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
