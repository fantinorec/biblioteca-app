<?php
/**
 * Pantalla generica de ABM para las tablas maestras simples
 * (Autores, Categorias, Editoriales). El "tipo" define la tabla, el
 * modulo de permisos y los campos del formulario.
 */
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/models/TablaMaestra.php';

const CONFIG_MAESTROS = [
    'autores' => [
        'titulo' => 'Autores',
        'tabla' => 'autores',
        'modulo' => 'autores',
        'campo_unico' => null,
        'campos' => [
            'nombre'       => ['etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true],
            'apellido'     => ['etiqueta' => 'Apellido', 'tipo' => 'text', 'requerido' => true],
            'nacionalidad' => ['etiqueta' => 'Nacionalidad', 'tipo' => 'text', 'requerido' => false],
        ],
    ],
    'categorias' => [
        'titulo' => 'Categorías',
        'tabla' => 'categorias',
        'modulo' => 'categorias',
        'campo_unico' => 'nombre',
        'campos' => [
            'nombre'      => ['etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true],
            'descripcion' => ['etiqueta' => 'Descripción', 'tipo' => 'textarea', 'requerido' => false],
        ],
    ],
    'editoriales' => [
        'titulo' => 'Editoriales',
        'tabla' => 'editoriales',
        'modulo' => 'editoriales',
        'campo_unico' => 'nombre',
        'campos' => [
            'nombre' => ['etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true],
            'pais'   => ['etiqueta' => 'País', 'tipo' => 'text', 'requerido' => false],
        ],
    ],
];

$tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? '';
if (!isset(CONFIG_MAESTROS[$tipo])) {
    http_response_code(404);
    die('Tabla maestra no encontrada.');
}
$config = CONFIG_MAESTROS[$tipo];

requerir_modulo($config['modulo']);
$puedeCrear = tiene_permiso($config['modulo'], 'crear');
$puedeEditar = tiene_permiso($config['modulo'], 'editar');
$puedeEliminar = tiene_permiso($config['modulo'], 'eliminar');

function volverMaestro(string $tipo, bool $ok, string $mensaje): never
{
    redirigir('maestro.php?tipo=' . urlencode($tipo) . '&' . ($ok ? 'ok' : 'error') . '=1&msg=' . urlencode($mensaje));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if (in_array($accion, ['crear', 'editar'], true)) {
        $puedeGuardar = $accion === 'crear' ? $puedeCrear : $puedeEditar;
        if (!$puedeGuardar) volverMaestro($tipo, false, 'No tenés permiso para esta acción.');

        $datos = [];
        $etiquetasRequeridas = [];
        foreach ($config['campos'] as $clave => $def) {
            $datos[$clave] = trim($_POST[$clave] ?? '');
            if ($def['requerido']) {
                $etiquetasRequeridas[$clave] = $def['etiqueta'];
            }
        }

        $errores = validar_campos_requeridos($datos, $etiquetasRequeridas);

        if (empty($errores) && $config['campo_unico']) {
            $campoUnico = $config['campo_unico'];
            $excluir = $accion === 'editar' ? $id : null;
            if (TablaMaestra::existeCampoUnico($config['tabla'], $campoUnico, $datos[$campoUnico], $excluir)) {
                $errores[$campoUnico] = 'Ya existe un registro con ese ' . $config['campos'][$campoUnico]['etiqueta'] . '.';
            }
        }

        if (!empty($errores)) {
            volverMaestro($tipo, false, 'Revisá los datos: ' . implode(' ', $errores));
        }

        if ($accion === 'crear') {
            $nuevoId = TablaMaestra::crear($config['tabla'], $datos);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'CREAR', $config['tabla'], $nuevoId, "Alta en {$config['titulo']}");
            volverMaestro($tipo, true, $config['titulo'] . ': registro creado.');
        } else {
            TablaMaestra::actualizar($config['tabla'], $id, $datos);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', $config['tabla'], $id, "Edición en {$config['titulo']} #$id");
            volverMaestro($tipo, true, $config['titulo'] . ': registro actualizado.');
        }
    }

    if ($accion === 'baja' && $puedeEliminar) {
        TablaMaestra::darBaja($config['tabla'], $id);
        registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'BAJA', $config['tabla'], $id, "Baja lógica en {$config['titulo']} #$id");
        volverMaestro($tipo, true, 'Registro dado de baja.');
    }

    if ($accion === 'reactivar' && $puedeEliminar) {
        TablaMaestra::reactivar($config['tabla'], $id);
        registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', $config['tabla'], $id, "Reactivación en {$config['titulo']} #$id");
        volverMaestro($tipo, true, 'Registro reactivado.');
    }

    volverMaestro($tipo, false, 'Acción no permitida.');
}

$registros = TablaMaestra::listar($config['tabla']);

$tituloPagina = $config['titulo'];
$claveModuloActual = $config['modulo'];
require __DIR__ . '/../src/partials/header.php';
?>
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
<?php require __DIR__ . '/../src/partials/footer.php'; ?>
