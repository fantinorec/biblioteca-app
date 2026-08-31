<?php
require_once __DIR__ . '/../src/bootstrap.php';
requerir_modulo('perfiles');
require_once __DIR__ . '/../src/models/Perfil.php';

$puedeCrear = tiene_permiso('perfiles', 'crear');
$puedeEditar = tiene_permiso('perfiles', 'editar');
$puedeEliminar = tiene_permiso('perfiles', 'eliminar');

function volverPerfiles(bool $ok, string $mensaje): never
{
    redirigir('perfiles.php?' . ($ok ? 'ok' : 'error') . '=1&msg=' . urlencode($mensaje));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($accion === 'crear' && $puedeCrear) {
        if ($nombre === '') volverPerfiles(false, 'El nombre del perfil es obligatorio.');
        if (Perfil::existeNombre($nombre)) volverPerfiles(false, 'Ya existe un perfil con ese nombre.');
        $id = Perfil::crear($nombre, $descripcion);
        registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'CREAR', 'perfiles', $id, "Alta de perfil \"$nombre\"");
        volverPerfiles(true, 'Perfil creado. Ahora asignale módulos desde "Permisos".');
    }

    if ($accion === 'editar' && $puedeEditar) {
        $id = (int) ($_POST['id'] ?? 0);
        $perfil = Perfil::buscarPorId($id);
        if ($perfil && (int) $perfil['es_administrador'] === 1) {
            volverPerfiles(false, 'El perfil Administrador no puede modificarse.');
        }
        if ($nombre === '') volverPerfiles(false, 'El nombre del perfil es obligatorio.');
        if (Perfil::existeNombre($nombre, $id)) volverPerfiles(false, 'Ya existe un perfil con ese nombre.');
        Perfil::actualizar($id, $nombre, $descripcion);
        registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', 'perfiles', $id, "Edición de perfil #$id");
        volverPerfiles(true, 'Perfil actualizado.');
    }

    if ($accion === 'baja' && $puedeEliminar) {
        $id = (int) ($_POST['id'] ?? 0);
        $perfil = Perfil::buscarPorId($id);
        if ($perfil && (int) $perfil['es_administrador'] === 1) {
            volverPerfiles(false, 'El perfil Administrador no puede darse de baja.');
        }
        if (Perfil::cantidadUsuariosConPerfil($id) > 0) {
            volverPerfiles(false, 'No se puede dar de baja: hay usuarios activos con este perfil.');
        }
        Perfil::darBaja($id);
        registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'BAJA', 'perfiles', $id, "Baja lógica de perfil #$id");
        volverPerfiles(true, 'Perfil dado de baja.');
    }

    if ($accion === 'reactivar' && $puedeEliminar) {
        $id = (int) ($_POST['id'] ?? 0);
        Perfil::reactivar($id);
        registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', 'perfiles', $id, "Reactivación de perfil #$id");
        volverPerfiles(true, 'Perfil reactivado.');
    }

    volverPerfiles(false, 'Acción no permitida.');
}

$perfiles = Perfil::listar();

$tituloPagina = 'Perfiles';
$claveModuloActual = 'perfiles';
require __DIR__ . '/../src/partials/header.php';
?>
<div class="toolbar">
    <h2 class="mb-0">Perfiles</h2>
    <?php if ($puedeCrear): ?>
        <button type="button" class="btn btn-primario" data-abrir-modal="modalPerfil" onclick="prepararAltaPerfil()">+ Nuevo perfil</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="tabla-wrap">
        <table class="tabla">
            <thead><tr><th>Nombre</th><th>Descripción</th><th>Tipo</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($perfiles as $p): ?>
                <tr>
                    <td><?= h($p['nombre']) ?></td>
                    <td><?= h($p['descripcion']) ?></td>
                    <td><?= $p['es_administrador'] ? '<span class="badge badge-activo">Administrador</span>' : 'Estándar' ?></td>
                    <td><span class="badge badge-<?= $p['estado'] ?>"><?= h($p['estado']) ?></span></td>
                    <td class="tabla-acciones">
                        <a href="permisos.php?perfil_id=<?= (int) $p['id'] ?>" class="btn btn-outline btn-sm">Permisos</a>
                        <?php if ($puedeEditar && !$p['es_administrador']): ?>
                            <button type="button" class="btn btn-outline btn-sm" data-abrir-modal="modalPerfil" onclick='prepararEdicionPerfil(<?= json_encode($p, JSON_UNESCAPED_UNICODE) ?>)'>Editar</button>
                        <?php endif; ?>
                        <?php if ($puedeEliminar && !$p['es_administrador']): ?>
                            <?php if ($p['estado'] === 'activo'): ?>
                                <button type="button" class="btn btn-peligro btn-sm" onclick="confirmarBajaPerfil(<?= (int) $p['id'] ?>, '<?= h(addslashes($p['nombre'])) ?>')">Baja</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-secundario btn-sm" onclick="reactivarPerfil(<?= (int) $p['id'] ?>)">Reactivar</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-fondo" id="modalPerfil">
    <div class="modal-caja">
        <h3 id="tituloModalPerfil">Nuevo perfil</h3>
        <form method="post" id="formPerfil">
            <input type="hidden" name="accion" id="accionPerfil" value="crear">
            <input type="hidden" name="id" id="idPerfil">
            <div class="form-grupo">
                <label>Nombre</label>
                <input class="form-control" type="text" name="nombre" id="nombrePerfil" required>
            </div>
            <div class="form-grupo">
                <label>Descripción</label>
                <textarea class="form-control" name="descripcion" id="descripcionPerfil" rows="3"></textarea>
            </div>
            <div class="modal-acciones">
                <button type="button" class="btn btn-secundario" data-cerrar-modal="modalPerfil">Cancelar</button>
                <button type="submit" class="btn btn-primario">Guardar</button>
            </div>
        </form>
    </div>
</div>

<form method="post" id="formAccionPerfil" class="oculto">
    <input type="hidden" name="accion" id="accionRapidaPerfil">
    <input type="hidden" name="id" id="idAccionRapidaPerfil">
</form>

<script>
function prepararAltaPerfil() {
    document.getElementById('tituloModalPerfil').textContent = 'Nuevo perfil';
    document.getElementById('accionPerfil').value = 'crear';
    document.getElementById('formPerfil').reset();
    document.getElementById('idPerfil').value = '';
}
function prepararEdicionPerfil(p) {
    document.getElementById('tituloModalPerfil').textContent = 'Editar perfil';
    document.getElementById('accionPerfil').value = 'editar';
    document.getElementById('idPerfil').value = p.id;
    document.getElementById('nombrePerfil').value = p.nombre;
    document.getElementById('descripcionPerfil').value = p.descripcion || '';
}
function confirmarBajaPerfil(id, nombre) {
    confirmarAccion('Dar de baja perfil', '¿Seguro que querés dar de baja el perfil "' + nombre + '"?', function () {
        document.getElementById('accionRapidaPerfil').value = 'baja';
        document.getElementById('idAccionRapidaPerfil').value = id;
        document.getElementById('formAccionPerfil').submit();
    });
}
function reactivarPerfil(id) {
    document.getElementById('accionRapidaPerfil').value = 'reactivar';
    document.getElementById('idAccionRapidaPerfil').value = id;
    document.getElementById('formAccionPerfil').submit();
}
</script>
<?php require __DIR__ . '/../src/partials/footer.php'; ?>
