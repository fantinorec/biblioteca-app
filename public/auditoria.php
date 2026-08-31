<?php
require_once __DIR__ . '/../src/bootstrap.php';
requerir_modulo('auditoria');

$pdo = obtenerConexion();

// Filtro simple por accion (opcional, via GET)
$filtroAccion = trim($_GET['accion'] ?? '');
$sql = 'SELECT * FROM auditoria';
$params = [];
if ($filtroAccion !== '') {
    $sql .= ' WHERE accion = :accion';
    $params['accion'] = $filtroAccion;
}
$sql .= ' ORDER BY fecha DESC LIMIT 300';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll();

$acciones = $pdo->query('SELECT DISTINCT accion FROM auditoria ORDER BY accion')->fetchAll(PDO::FETCH_COLUMN);

$tituloPagina = 'Auditoría';
$claveModuloActual = 'auditoria';
require __DIR__ . '/../src/partials/header.php';
?>
<h2>Historial de auditoría</h2>
<p class="texto-suave">Registro de altas, bajas, modificaciones e inicios de sesión (se muestran los últimos 300 eventos).</p>

<div class="card">
    <form method="get" class="form-grupo" style="max-width:280px;">
        <label>Filtrar por acción</label>
        <select class="form-control" name="accion" onchange="this.form.submit()">
            <option value="">Todas</option>
            <?php foreach ($acciones as $a): ?>
                <option value="<?= h($a) ?>" <?= $filtroAccion === $a ? 'selected' : '' ?>><?= h($a) ?></option>
            <?php endforeach; ?>
        </select>
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
<?php require __DIR__ . '/../src/partials/footer.php'; ?>
