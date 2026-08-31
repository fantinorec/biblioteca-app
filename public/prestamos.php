<?php
require_once __DIR__ . '/../src/bootstrap.php';
requerir_modulo('prestamos');
require_once __DIR__ . '/../src/models/Prestamo.php';
require_once __DIR__ . '/../src/models/Libro.php';
require_once __DIR__ . '/../src/models/Usuario.php';

Prestamo::actualizarVencidos();

$puedeCrear = tiene_permiso('prestamos', 'crear');
$puedeGestionarTodos = tiene_permiso('prestamos', 'editar'); // bibliotecario/admin: ve todos y puede marcar devoluciones

function volverPrestamos(bool $ok, string $mensaje): never
{
    redirigir('prestamos.php?' . ($ok ? 'ok' : 'error') . '=1&msg=' . urlencode($mensaje));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear' && $puedeCrear) {
        $libroId = (int) ($_POST['libro_id'] ?? 0);
        $fechaDevolucion = trim($_POST['fecha_devolucion_estimada'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');

        // Si el perfil no gestiona todos los prestamos (es un Lector), solo puede pedir para si mismo.
        $usuarioId = $puedeGestionarTodos ? (int) ($_POST['usuario_id'] ?? 0) : (int) $_SESSION['usuario_id'];

        $errores = validar_campos_requeridos(
            ['libro_id' => $libroId, 'usuario_id' => $usuarioId, 'fecha_devolucion_estimada' => $fechaDevolucion],
            ['libro_id' => 'Libro', 'usuario_id' => 'Usuario', 'fecha_devolucion_estimada' => 'Fecha de devolución estimada']
        );
        if (empty($errores) && strtotime($fechaDevolucion) <= time()) {
            $errores['fecha_devolucion_estimada'] = 'La fecha de devolución debe ser futura.';
        }
        if (!empty($errores)) {
            volverPrestamos(false, 'Revisá los datos: ' . implode(' ', $errores));
        }

        try {
            $id = Prestamo::registrar($usuarioId, $libroId, $fechaDevolucion, $_SESSION['usuario_id'], $observaciones);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'CREAR', 'prestamos', $id, "Alta de préstamo (libro #$libroId, usuario #$usuarioId)");
            volverPrestamos(true, 'Préstamo registrado correctamente.');
        } catch (RuntimeException $e) {
            volverPrestamos(false, $e->getMessage());
        }
    }

    if ($accion === 'devolver' && $puedeGestionarTodos) {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            Prestamo::registrarDevolucion($id);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', 'prestamos', $id, "Registro de devolución de préstamo #$id");
            volverPrestamos(true, 'Devolución registrada. Se repuso el stock del libro.');
        } catch (RuntimeException $e) {
            volverPrestamos(false, $e->getMessage());
        }
    }

    volverPrestamos(false, 'Acción no permitida.');
}

$prestamos = $puedeGestionarTodos ? Prestamo::listar() : Prestamo::listar(null, (int) $_SESSION['usuario_id']);
$librosDisponibles = array_filter(Libro::listar('activo'), fn($l) => (int) $l['stock_disponible'] > 0);
$usuariosActivos = $puedeGestionarTodos ? Usuario::listar('activo') : [];

$tituloPagina = 'Préstamos';
$claveModuloActual = 'prestamos';
require __DIR__ . '/../src/partials/header.php';
?>
<div class="toolbar">
    <h2 class="mb-0"><?= $puedeGestionarTodos ? 'Préstamos de la biblioteca' : 'Mis préstamos' ?></h2>
    <?php if ($puedeCrear): ?>
        <button type="button" class="btn btn-primario" data-abrir-modal="modalPrestamo">+ Nuevo préstamo</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
            <tr>
                <th>Libro</th>
                <?php if ($puedeGestionarTodos): ?><th>Lector</th><?php endif; ?>
                <th>Fecha préstamo</th><th>Devolución estimada</th><th>Estado</th>
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
                    <td><span class="badge badge-<?= $p['estado'] ?>"><?= h($p['estado']) ?></span></td>
                    <?php if ($puedeGestionarTodos): ?>
                        <td class="tabla-acciones">
                            <?php if ($p['estado'] !== 'devuelto'): ?>
                                <button type="button" class="btn btn-outline btn-sm" onclick="confirmarDevolucion(<?= (int) $p['id'] ?>)">Registrar devolución</button>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($prestamos)): ?>
                <tr><td colspan="6" class="texto-suave">No hay préstamos para mostrar.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-fondo" id="modalPrestamo">
    <div class="modal-caja">
        <h3>Nuevo préstamo</h3>
        <form method="post">
            <input type="hidden" name="accion" value="crear">
            <?php if ($puedeGestionarTodos): ?>
                <div class="form-grupo">
                    <label>Lector</label>
                    <select class="form-control" name="usuario_id" required>
                        <?php foreach ($usuariosActivos as $u): ?>
                            <option value="<?= (int) $u['id'] ?>"><?= h($u['nombre'] . ' ' . $u['apellido'] . ' (' . $u['email'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
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
            <div class="form-grupo">
                <label>Fecha de devolución estimada</label>
                <input class="form-control" type="date" name="fecha_devolucion_estimada" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
            </div>
            <div class="form-grupo">
                <label>Observaciones (opcional)</label>
                <textarea class="form-control" name="observaciones" rows="2"></textarea>
            </div>
            <div class="modal-acciones">
                <button type="button" class="btn btn-secundario" data-cerrar-modal="modalPrestamo">Cancelar</button>
                <button type="submit" class="btn btn-primario">Registrar préstamo</button>
            </div>
        </form>
    </div>
</div>

<form method="post" id="formDevolucion" class="oculto">
    <input type="hidden" name="accion" value="devolver">
    <input type="hidden" name="id" id="idDevolucion">
</form>

<script>
function confirmarDevolucion(id) {
    confirmarAccion('Registrar devolución', '¿Confirmás la devolución de este libro? Se repondrá el stock automáticamente.', function () {
        document.getElementById('idDevolucion').value = id;
        document.getElementById('formDevolucion').submit();
    });
}
</script>
<?php require __DIR__ . '/../src/partials/footer.php'; ?>
