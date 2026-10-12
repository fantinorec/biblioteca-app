<?php require __DIR__ . '/../../partials/header.php'; ?>
<h2><?= $puedeVerTodos ? 'Historial de auditoría' : 'Mis movimientos' ?></h2>
<p class="texto-suave">
    <?= $puedeVerTodos
        ? 'Registro de altas, bajas, modificaciones e inicios de sesión de todo el sistema (se muestran los últimos 300 eventos).'
        : 'Tus acciones dentro del sistema: solicitudes y devoluciones de préstamos, inicios de sesión y más (se muestran los últimos 300 eventos).' ?>
</p>

<div class="card">
    <form method="get" class="<?= $puedeVerTodos ? 'grid-2' : '' ?>">
        <div class="form-grupo" style="margin-bottom:<?= $puedeVerTodos ? '0' : '' ?>;">
            <label>Filtrar por acción</label>
            <select class="form-control" name="accion" onchange="this.form.submit()">
                <option value="">Todas</option>
                <option value="__prestamos__" <?= $filtroAccion === '__prestamos__' ? 'selected' : '' ?>>Préstamos (pedidos y devoluciones)</option>
                <?php foreach ($acciones as $a): ?>
                    <option value="<?= h($a) ?>" <?= $filtroAccion === $a ? 'selected' : '' ?>><?= h($a) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($puedeVerTodos): ?>
            <div class="form-grupo" style="margin-bottom:0;">
                <label>Buscar por email de usuario</label>
                <div style="display:flex; gap:8px;">
                    <input class="form-control" type="text" name="usuario" value="<?= h($filtroUsuario) ?>" placeholder="ej: ana@correo.com">
                    <button type="submit" class="btn btn-primario">Buscar</button>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="tabla-wrap">
        <table class="tabla">
            <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Tabla</th><th>Registro</th><th>Detalle</th><th>IP</th></tr></thead>
            <tbody>
            <?php foreach ($registros as $r): ?>
                <tr>
                    <td><?= h($r['fecha']) ?></td>
                    <td><?= h($r['usuario_email'] ?? '—') ?></td>
                    <td><span class="badge badge-activo"><?= h($r['accion']) ?></span></td>
                    <td><?= h($r['tabla_afectada'] ?? '—') ?></td>
                    <td><?= h((string) ($r['registro_id'] ?? '')) ?></td>
                    <td><?= h($r['detalle'] ?? '') ?></td>
                    <td><?= h($r['ip'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($registros)): ?>
                <tr><td colspan="7" class="texto-suave">No hay eventos registrados todavía.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
