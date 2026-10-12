<?php require __DIR__ . '/../../partials/header.php'; ?>
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
<?php require __DIR__ . '/../../partials/footer.php'; ?>
