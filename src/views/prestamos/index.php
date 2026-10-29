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
    <p class="texto-suave mb-2">Tus solicitudes quedan <span class="badge badge-pendiente">pendiente</span> hasta que un bibliotecario o administrador las revise. Si pedís más de un libro (o varias copias de uno) a la vez, se aprueban y se devuelven siempre todos juntos.</p>
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
                <th>Libro(s)</th>
                <?php if ($puedeGestionarTodos): ?><th>Lector</th><?php endif; ?>
                <th>Fecha solicitud</th><th>Devolución estimada</th><th>Devolución real</th><th>Estado</th>
                <?php if ($puedeGestionarTodos): ?><th>Acciones</th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($prestamos as $p): ?>
                <?php $totalLibros = array_sum($p['libros']); ?>
                <tr>
                    <td>
                        <?php if ($totalLibros > 1): ?>
                            <div style="font-weight:600; margin-bottom:2px;">Préstamo múltiple (<?= $totalLibros ?> libros)</div>
                            <ul style="margin:0; padding-left:18px;">
                                <?php foreach ($p['libros'] as $titulo => $cantidad): ?>
                                    <li><?= h($titulo) ?><?= $cantidad > 1 ? ' × ' . $cantidad : '' ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <?php foreach ($p['libros'] as $titulo => $cantidad): ?>
                                <?= h($titulo) ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
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
                                <button type="button" class="btn btn-outline btn-sm" onclick='abrirModalDevolucion(<?= json_encode($p["filas"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>)'>Registrar devolución</button>
                                <button type="button" class="btn btn-secundario btn-sm" onclick="confirmarCancelacion(<?= (int) $p['id'] ?>, 'aprobacion')" title="Deshacer la aprobación por si fue un error">Cancelar</button>
                            <?php elseif ($p['estado'] === 'vencido'): ?>
                                <button type="button" class="btn btn-outline btn-sm" onclick='abrirModalDevolucion(<?= json_encode($p["filas"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>)'>Registrar devolución</button>
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
                <label>Libro(s) y cantidad — podés pedir más de un título, y más de un ejemplar del mismo (hasta el stock disponible)</label>
                <div style="max-height:220px; overflow-y:auto; border:1px solid #d8cdb0; border-radius:8px; padding:8px;">
                    <?php foreach ($librosDisponibles as $l): ?>
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; padding:4px 0;">
                            <span><?= h($l['titulo']) ?> (<?= (int) $l['stock_disponible'] ?> disponibles)</span>
                            <input
                                type="number"
                                class="form-control input-cantidad-libro"
                                style="width:72px; text-align:center;"
                                name="libros[<?= (int) $l['id'] ?>]"
                                min="0"
                                max="<?= (int) $l['stock_disponible'] ?>"
                                value="0">
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($librosDisponibles)): ?>
                        <p class="texto-suave" style="margin:0;">No hay libros con stock disponible en este momento.</p>
                    <?php endif; ?>
                </div>
            </div>
            <p class="texto-suave" style="margin-top:-4px;">El plazo de devolución es fijo: 3 meses desde el día en que se presta el libro. Si pedís más de un ejemplar, se devuelven siempre todos juntos.</p>
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

<div class="modal-fondo" id="modalDevolucion">
    <div class="modal-caja">
        <h3>Registrar devolución</h3>
        <p class="texto-suave" id="textoModalDevolucion"></p>
        <form method="post" id="formDevolucion">
            <input type="hidden" name="accion" value="devolver">
            <input type="hidden" name="id" id="idDevolucion">
            <div class="form-grupo">
                <label>¿En qué estado volvió cada libro?</label>
                <div id="listaEstadoDevolucion" style="max-height:220px; overflow-y:auto; border:1px solid #d8cdb0; border-radius:8px; padding:8px;"></div>
                <span class="form-ayuda">Marcá "Dañado" solo en los ejemplares que correspondan: cada uno descuenta una estrella de la calificación del lector (y una más, en total, si la devolución llega tarde).</span>
            </div>
            <div class="modal-acciones">
                <button type="button" class="btn btn-secundario" data-cerrar-modal="modalDevolucion">Cancelar</button>
                <button type="submit" class="btn btn-primario">Confirmar devolución</button>
            </div>
        </form>
    </div>
</div>
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
function abrirModalDevolucion(filas) {
    if (!filas || !filas.length) return;

    document.getElementById('idDevolucion').value = filas[0].id;
    document.getElementById('textoModalDevolucion').textContent = filas.length > 1
        ? 'Este préstamo incluye ' + filas.length + ' libros: se devuelven todos juntos (se repone el stock de cada uno), pero podés marcar el estado de cada ejemplar por separado.'
        : 'Se va a reponer el stock del libro.';

    var contenedor = document.getElementById('listaEstadoDevolucion');
    contenedor.innerHTML = '';

    // Si hay más de una copia del mismo título, los numeramos (ejemplar 1, 2, ...) para distinguirlos.
    var contadorPorTitulo = {};
    filas.forEach(function (fila) {
        contadorPorTitulo[fila.titulo] = (contadorPorTitulo[fila.titulo] || 0) + 1;
    });
    var vistoPorTitulo = {};

    filas.forEach(function (fila) {
        var etiqueta = fila.titulo;
        if (contadorPorTitulo[fila.titulo] > 1) {
            vistoPorTitulo[fila.titulo] = (vistoPorTitulo[fila.titulo] || 0) + 1;
            etiqueta += ' (ejemplar ' + vistoPorTitulo[fila.titulo] + ')';
        }

        var filaDiv = document.createElement('div');
        filaDiv.style.cssText = 'display:flex; align-items:center; justify-content:space-between; gap:8px; padding:4px 0;';

        var span = document.createElement('span');
        span.textContent = etiqueta;

        var select = document.createElement('select');
        select.className = 'form-control';
        select.style.cssText = 'width:150px;';
        select.name = 'estado_libro[' + fila.id + ']';
        select.innerHTML = '<option value="bueno">Buen estado</option><option value="danado">Dañado</option>';

        filaDiv.appendChild(span);
        filaDiv.appendChild(select);
        contenedor.appendChild(filaDiv);
    });

    window.abrirModal('modalDevolucion');
}
function confirmarAprobacion(id) {
    confirmarAccion('Aprobar solicitud', '¿Confirmás este préstamo? Si la solicitud incluye más de un libro, se prestan todos juntos y se descuenta el stock de cada uno.', function () {
        document.getElementById('idAprobacion').value = id;
        document.getElementById('formAprobacion').submit();
    });
}
function confirmarRechazo(id) {
    confirmarAccion('Rechazar solicitud', '¿Seguro que querés rechazar esta solicitud? Si incluye más de un libro, se rechazan todos juntos.', function () {
        document.getElementById('idRechazo').value = id;
        document.getElementById('formRechazo').submit();
    });
}
function confirmarCancelacion(id, tipo) {
    var mensaje = tipo === 'aprobacion'
        ? '¿Seguro que querés cancelar esta aprobación? Si el préstamo incluye más de un libro, se cancelan todos juntos: vuelven al stock y la solicitud vuelve a quedar pendiente para revisarla de nuevo.'
        : '¿Seguro que querés cancelar este rechazo? Si la solicitud incluye más de un libro, se cancelan todos juntos para revisarlos de nuevo.';
    confirmarAccion('Cancelar decisión', mensaje, function () {
        document.getElementById('idCancelacion').value = id;
        document.getElementById('formCancelacion').submit();
    });
}

// Si llegamos desde "Pedir préstamo" en el catálogo (?libro_id=X), abrimos el
// modal de alta con la cantidad de ese libro en 1.
(function () {
    var params = new URLSearchParams(window.location.search);
    var libroId = params.get('libro_id');
    if (!libroId) return;
    var input = document.querySelector('#modalPrestamo input[name="libros[' + libroId + ']"]');
    if (input) input.value = 1;
    if (typeof abrirModal === 'function') abrirModal('modalPrestamo');
})();

// Hay que pedir al menos un libro (cantidad mayor a cero) antes de poder enviar el formulario.
(function () {
    var formPrestamo = document.querySelector('#modalPrestamo form');
    if (!formPrestamo) return;
    formPrestamo.addEventListener('submit', function (evento) {
        var inputsCantidad = formPrestamo.querySelectorAll('.input-cantidad-libro');
        var algunoPedido = false;
        for (var i = 0; i < inputsCantidad.length; i++) {
            if (parseInt(inputsCantidad[i].value, 10) > 0) {
                algunoPedido = true;
                break;
            }
        }
        if (!algunoPedido) {
            evento.preventDefault();
            mostrarToast('error', 'Revisá el formulario', 'Elegí al menos un libro, con una cantidad mayor a cero, para prestar.');
        }
    });
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
