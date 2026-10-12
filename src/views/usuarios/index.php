<?php require __DIR__ . '/../../partials/header.php'; ?>
<div class="toolbar">
    <h2 class="mb-0">Usuarios del sistema</h2>
    <?php if ($puedeCrear): ?>
        <button type="button" class="btn btn-primario" data-abrir-modal="modalUsuario" onclick="prepararAlta()">+ Nuevo usuario</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
            <tr>
                <th>Nombre</th><th>Email</th><th>Perfil</th><th>Estado</th><th>Verificado</th><th>Último login</th><th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= h($u['nombre'] . ' ' . $u['apellido']) ?></td>
                    <td><?= h($u['email']) ?></td>
                    <td><?= h($u['perfil_nombre']) ?></td>
                    <td><span class="badge badge-<?= $u['estado'] ?>"><?= h($u['estado']) ?></span></td>
                    <td><?= ((int) $u['email_verificado']) ? 'Sí' : 'No' ?></td>
                    <td><?= h($u['ultimo_login'] ?? '—') ?></td>
                    <td class="tabla-acciones">
                        <?php if ($puedeEditar): ?>
                            <button type="button" class="btn btn-outline btn-sm" onclick='prepararEdicion(<?= json_encode($u, JSON_UNESCAPED_UNICODE) ?>)' data-abrir-modal="modalUsuario">Editar</button>
                        <?php endif; ?>
                        <?php if ($puedeEliminar): ?>
                            <?php if ($u['estado'] === 'activo'): ?>
                                <button type="button" class="btn btn-peligro btn-sm" onclick="confirmarBaja(<?= (int) $u['id'] ?>, '<?= h(addslashes($u['nombre'])) ?>')">Baja</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-secundario btn-sm" onclick="reactivar(<?= (int) $u['id'] ?>)">Reactivar</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal alta/edición -->
<div class="modal-fondo" id="modalUsuario">
    <div class="modal-caja">
        <h3 id="tituloModalUsuario">Nuevo usuario</h3>
        <form method="post" id="formUsuario">
            <input type="hidden" name="accion" id="accionUsuario" value="crear">
            <input type="hidden" name="id" id="idUsuario">
            <div class="form-grupo">
                <label>Nombre</label>
                <input class="form-control" type="text" name="nombre" id="nombreUsuario" required>
            </div>
            <div class="form-grupo">
                <label>Apellido</label>
                <input class="form-control" type="text" name="apellido" id="apellidoUsuario" required>
            </div>
            <div class="form-grupo">
                <label>Email</label>
                <input class="form-control" type="email" name="email" id="emailUsuario" data-validar="email" required>
            </div>
            <div class="form-grupo">
                <label>Perfil</label>
                <select class="form-control" name="perfil_id" id="perfilUsuario" required>
                    <?php foreach ($perfiles as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= h($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grupo">
                <label id="labelPasswordUsuario">Contraseña</label>
                <input class="form-control" type="password" name="password" id="passwordUsuario" data-validar="password">
                <span class="form-ayuda"><?= h(mensaje_politica_password()) ?></span>
                <span class="form-error"></span>
            </div>
            <div class="modal-acciones">
                <button type="button" class="btn btn-secundario" data-cerrar-modal="modalUsuario">Cancelar</button>
                <button type="submit" class="btn btn-primario">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Formulario oculto reutilizado para baja/reactivar -->
<form method="post" id="formAccionUsuario" class="oculto">
    <input type="hidden" name="accion" id="accionRapidaUsuario">
    <input type="hidden" name="id" id="idAccionRapidaUsuario">
</form>

<script>
function prepararAlta() {
    document.getElementById('tituloModalUsuario').textContent = 'Nuevo usuario';
    document.getElementById('accionUsuario').value = 'crear';
    document.getElementById('formUsuario').reset();
    document.getElementById('idUsuario').value = '';
    document.getElementById('labelPasswordUsuario').textContent = 'Contraseña';
    document.getElementById('passwordUsuario').required = true;
}
function prepararEdicion(u) {
    document.getElementById('tituloModalUsuario').textContent = 'Editar usuario';
    document.getElementById('accionUsuario').value = 'editar';
    document.getElementById('idUsuario').value = u.id;
    document.getElementById('nombreUsuario').value = u.nombre;
    document.getElementById('apellidoUsuario').value = u.apellido;
    document.getElementById('emailUsuario').value = u.email;
    document.getElementById('perfilUsuario').value = u.perfil_id;
    document.getElementById('passwordUsuario').value = '';
    document.getElementById('passwordUsuario').required = false;
    document.getElementById('labelPasswordUsuario').textContent = 'Nueva contraseña (dejar vacío para no cambiarla)';
}
function confirmarBaja(id, nombre) {
    confirmarAccion('Dar de baja usuario', '¿Seguro que querés dar de baja a "' + nombre + '"?', function () {
        document.getElementById('accionRapidaUsuario').value = 'baja';
        document.getElementById('idAccionRapidaUsuario').value = id;
        document.getElementById('formAccionUsuario').submit();
    });
}
function reactivar(id) {
    document.getElementById('accionRapidaUsuario').value = 'reactivar';
    document.getElementById('idAccionRapidaUsuario').value = id;
    document.getElementById('formAccionUsuario').submit();
}
// OJO: esta vista se arma con header.php/footer.php, y el script
// validaciones.js se carga recien en footer.php, DESPUES de este bloque.
// Si llamamos activarValidacionFormulario() aca directo, todavia no existe
// y tira "no esta definida". Por eso esperamos a que termine de cargar toda
// la pagina (DOMContentLoaded) antes de engancharla.
document.addEventListener('DOMContentLoaded', function () {
    activarValidacionFormulario('formUsuario');
});
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
