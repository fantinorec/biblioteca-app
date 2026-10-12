<?php require __DIR__ . '/../../partials/header.php'; ?>
<div class="toolbar">
    <h2 class="mb-0"><?= h($config['titulo']) ?></h2>
    <?php if ($puedeCrear): ?>
        <button type="button" class="btn btn-primario" data-abrir-modal="modalMaestro" onclick="prepararAltaMaestro()">+ Nuevo</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
            <tr>
                <?php foreach ($config['campos'] as $def): ?><th><?= h($def['etiqueta']) ?></th><?php endforeach; ?>
                <th>Estado</th><th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($registros as $r): ?>
                <tr>
                    <?php foreach ($config['campos'] as $clave => $def): ?>
                        <td><?= h((string) ($r[$clave] ?? '')) ?></td>
                    <?php endforeach; ?>
                    <td><span class="badge badge-<?= $r['estado'] ?>"><?= h($r['estado']) ?></span></td>
                    <td class="tabla-acciones">
                        <?php if ($puedeEditar): ?>
                            <button type="button" class="btn btn-outline btn-sm" data-abrir-modal="modalMaestro" onclick='prepararEdicionMaestro(<?= json_encode($r, JSON_UNESCAPED_UNICODE) ?>)'>Editar</button>
                        <?php endif; ?>
                        <?php if ($puedeEliminar): ?>
                            <?php if ($r['estado'] === 'activo'): ?>
                                <button type="button" class="btn btn-peligro btn-sm" onclick="confirmarBajaMaestro(<?= (int) $r['id'] ?>)">Baja</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-secundario btn-sm" onclick="reactivarMaestro(<?= (int) $r['id'] ?>)">Reactivar</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($registros)): ?>
                <tr><td colspan="<?= count($config['campos']) + 2 ?>" class="texto-suave">No hay registros cargados todavía.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-fondo" id="modalMaestro">
    <div class="modal-caja">
        <h3 id="tituloModalMaestro">Nuevo</h3>
        <form method="post" id="formMaestro">
            <input type="hidden" name="tipo" value="<?= h($tipo) ?>">
            <input type="hidden" name="accion" id="accionMaestro" value="crear">
            <input type="hidden" name="id" id="idMaestro">
            <?php foreach ($config['campos'] as $clave => $def): ?>
                <div class="form-grupo">
                    <label><?= h($def['etiqueta']) ?></label>
                    <?php if ($def['tipo'] === 'textarea'): ?>
                        <textarea class="form-control" name="<?= h($clave) ?>" id="campo_<?= h($clave) ?>" rows="3" <?= $def['requerido'] ? 'required' : '' ?>></textarea>
                    <?php else: ?>
                        <input class="form-control" type="text" name="<?= h($clave) ?>" id="campo_<?= h($clave) ?>" <?= $def['requerido'] ? 'required' : '' ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="modal-acciones">
                <button type="button" class="btn btn-secundario" data-cerrar-modal="modalMaestro">Cancelar</button>
                <button type="submit" class="btn btn-primario">Guardar</button>
            </div>
        </form>
    </div>
</div>

<form method="post" id="formAccionMaestro" class="oculto">
    <input type="hidden" name="tipo" value="<?= h($tipo) ?>">
    <input type="hidden" name="accion" id="accionRapidaMaestro">
    <input type="hidden" name="id" id="idAccionRapidaMaestro">
</form>

<script>
const camposMaestro = <?= json_encode(array_keys($config['campos'])) ?>;
function prepararAltaMaestro() {
    document.getElementById('tituloModalMaestro').textContent = 'Nuevo';
    document.getElementById('accionMaestro').value = 'crear';
    document.getElementById('formMaestro').reset();
    document.getElementById('idMaestro').value = '';
}
function prepararEdicionMaestro(registro) {
    document.getElementById('tituloModalMaestro').textContent = 'Editar';
    document.getElementById('accionMaestro').value = 'editar';
    document.getElementById('idMaestro').value = registro.id;
    camposMaestro.forEach(function (clave) {
        const el = document.getElementById('campo_' + clave);
        if (el) el.value = registro[clave] || '';
    });
}
function confirmarBajaMaestro(id) {
    confirmarAccion('Dar de baja', '¿Seguro que querés dar de baja este registro?', function () {
        document.getElementById('accionRapidaMaestro').value = 'baja';
        document.getElementById('idAccionRapidaMaestro').value = id;
        document.getElementById('formAccionMaestro').submit();
    });
}
function reactivarMaestro(id) {
    document.getElementById('accionRapidaMaestro').value = 'reactivar';
    document.getElementById('idAccionRapidaMaestro').value = id;
    document.getElementById('formAccionMaestro').submit();
}
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
