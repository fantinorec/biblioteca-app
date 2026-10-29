<?php require __DIR__ . '/../../partials/header.php'; ?>
<div class="toolbar">
    <h2 class="mb-0">Calificaciones de lectores</h2>
</div>
<p class="texto-suave mb-2">Buscá un lector para ver su calificación actual (empieza en 5 estrellas) y, si bajó, el motivo de cada cambio — útil si el lector reclama por qué puede pedir menos libros.</p>

<div class="card">
    <form method="get">
        <div class="form-grupo" style="margin-bottom:0;">
            <label>Buscar por nombre, apellido o email</label>
            <div style="display:flex; gap:8px;">
                <input class="form-control" type="text" name="buscar" value="<?= h($busqueda) ?>" placeholder="ej: Ana Gómez o ana@correo.com">
                <button type="submit" class="btn btn-primario">Buscar</button>
                <?php if ($busqueda !== '' || $usuarioSeleccionado): ?>
                    <a href="calificaciones.php" class="btn btn-secundario">Limpiar</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<?php if ($busqueda !== '' && !$usuarioSeleccionado): ?>
<div class="card">
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
            <tr><th>Nombre</th><th>Email</th><th>Perfil</th><th>Calificación</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= h($u['nombre'] . ' ' . $u['apellido']) ?></td>
                    <td><?= h($u['email']) ?></td>
                    <td><?= h($u['perfil_nombre']) ?></td>
                    <td><?= estrellas_texto((int) $u['calificacion']) ?></td>
                    <td class="tabla-acciones">
                        <a class="btn btn-outline btn-sm" href="calificaciones.php?buscar=<?= urlencode($busqueda) ?>&usuario_id=<?= (int) $u['id'] ?>">Ver detalle</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($usuarios)): ?>
                <tr><td colspan="5" class="texto-suave">No se encontró ningún usuario que coincida con "<?= h($busqueda) ?>".</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($usuarioSeleccionado): ?>
<div class="card">
    <div class="toolbar" style="margin-bottom:10px;">
        <h3 class="mb-0"><?= h($usuarioSeleccionado['nombre'] . ' ' . $usuarioSeleccionado['apellido']) ?></h3>
        <a href="calificaciones.php<?= $busqueda !== '' ? '?buscar=' . urlencode($busqueda) : '' ?>" class="btn btn-secundario btn-sm">← Volver a la búsqueda</a>
    </div>
    <p class="texto-suave mb-1"><?= h($usuarioSeleccionado['email']) ?> · <?= h($usuarioSeleccionado['perfil_nombre']) ?></p>

    <?php $calificacionActual = (int) $usuarioSeleccionado['calificacion']; ?>
    <p style="font-size:26px; margin:10px 0 2px;"><?= estrellas_texto($calificacionActual) ?></p>
    <p class="texto-suave" style="margin-top:0;">
        <?= $calificacionActual ?>/5 — puede tener hasta <?= $calificacionActual ?> libro<?= $calificacionActual === 1 ? '' : 's' ?> a la vez (entre pendientes y prestados).
    </p>

    <?php if ($calificacionActual >= 5 && empty($historial)): ?>
        <div class="card" style="background:#f4efe6; box-shadow:none; margin-top:10px;">
            <p class="mb-0">Nunca tuvo una devolución tardía ni un libro dañado: calificación perfecta.</p>
        </div>
    <?php else: ?>
        <h4 class="mb-1" style="margin-top:18px;">Historial de cambios</h4>
        <ul style="margin:0; padding:0; list-style:none;">
            <?php foreach ($historial as $h): ?>
                <li style="display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--color-borde);">
                    <span class="badge <?= $h['delta'] < 0 ? 'badge-rechazado' : 'badge-activo' ?>"><?= $h['delta'] > 0 ? '+' : '' ?><?= (int) $h['delta'] ?> ★</span>
                    <span style="flex:1;"><?= h($h['motivo']) ?></span>
                    <span class="texto-suave" style="font-size:12.5px; white-space:nowrap;"><?= h($h['fecha_creacion']) ?></span>
                </li>
            <?php endforeach; ?>
            <?php if (empty($historial)): ?>
                <li class="texto-suave">Sin cambios registrados todavía.</li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
