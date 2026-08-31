<?php
require_once __DIR__ . '/../src/bootstrap.php';
requerir_modulo('permisos');
require_once __DIR__ . '/../src/models/Perfil.php';

$perfiles = Perfil::listar('activo');
$perfilId = (int) ($_GET['perfil_id'] ?? ($perfiles[0]['id'] ?? 0));
$perfilActual = Perfil::buscarPorId($perfilId);

if (!$perfilActual) {
    redirigir('perfiles.php');
}

$esAdministrador = (int) $perfilActual['es_administrador'] === 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($esAdministrador) {
        redirigir('permisos.php?perfil_id=' . $perfilId . '&error=1&msg=' . urlencode('El perfil Administrador siempre tiene acceso total y no puede modificarse.'));
    }

    $permisosPost = $_POST['permiso'] ?? []; // [modulo_id => ['ver'=>on, 'crear'=>on, ...]]
    Perfil::guardarPermisos($perfilId, $permisosPost);
    registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', 'perfil_modulo', $perfilId, "Actualización de permisos del perfil #$perfilId");

    // Si el usuario logueado tiene el perfil que se acaba de modificar, refrescamos su sesion al toque.
    if ((int) $_SESSION['perfil_id'] === $perfilId) {
        refrescar_permisos_en_sesion($perfilId);
    }
    redirigir('permisos.php?perfil_id=' . $perfilId . '&ok=1&msg=' . urlencode('Permisos actualizados correctamente.'));
}

$matrizPermisos = Perfil::permisosCompletos($perfilId);

$tituloPagina = 'Permisos';
$claveModuloActual = 'permisos';
require __DIR__ . '/../src/partials/header.php';
?>
<h2>Asignación de módulos por perfil</h2>
<p class="texto-suave">Elegí un perfil y marcá qué módulos puede ver, y qué puede hacer en cada uno (por ejemplo, que el perfil "Lector" solo pueda ver el catálogo de Libros).</p>

<div class="card">
    <form method="get" class="form-grupo" style="max-width:320px;">
        <label>Perfil</label>
        <select class="form-control" name="perfil_id" onchange="this.form.submit()">
            <?php foreach ($perfiles as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $p['id'] == $perfilId ? 'selected' : '' ?>>
                    <?= h($p['nombre']) ?><?= $p['es_administrador'] ? ' (acceso total)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="card">
    <?php if ($esAdministrador): ?>
        <div class="card" style="background:#fdeecb;border-color:#e9d191;">
            El perfil <strong>Administrador</strong> tiene acceso total a todos los módulos por diseño y no puede restringirse, para evitar que el sistema quede sin nadie con permisos completos.
        </div>
    <?php endif; ?>
    <form method="post">
        <div class="tabla-wrap">
            <table class="tabla">
                <thead><tr><th>Módulo</th><th>Ver</th><th>Crear</th><th>Editar</th><th>Eliminar</th></tr></thead>
                <tbody>
                <?php foreach ($matrizPermisos as $fila): $mid = (int) $fila['modulo_id']; ?>
                    <tr>
                        <td><?= h($fila['nombre']) ?></td>
                        <?php foreach (['ver', 'crear', 'editar', 'eliminar'] as $permiso): ?>
                            <td>
                                <input type="checkbox"
                                       name="permiso[<?= $mid ?>][<?= $permiso ?>]"
                                       <?= $fila["puede_$permiso"] || $esAdministrador ? 'checked' : '' ?>
                                       <?= $esAdministrador ? 'disabled' : '' ?>>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (!$esAdministrador): ?>
            <button type="submit" class="btn btn-primario mt-2">Guardar permisos</button>
        <?php endif; ?>
    </form>
</div>
<?php require __DIR__ . '/../src/partials/footer.php'; ?>
