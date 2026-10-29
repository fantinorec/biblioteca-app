    </main>
</div>

<script src="<?= asset_url('assets/js/toast.js') ?>"></script>
<script src="<?= asset_url('assets/js/modal.js') ?>"></script>
<script src="<?= asset_url('assets/js/session.js') ?>"></script>
<script src="<?= asset_url('assets/js/validaciones.js') ?>"></script>
<script>
    // Sincroniza el cache local (localStorage) con la sesion real
    // en cada carga de pantalla interna, para que el nombre de arriba a
    // la derecha este siempre al dia (por ejemplo, justo despues de loguearse).Mostar en cllase
    guardarSesionLocal({
        nombre: <?= json_encode($_SESSION['nombre'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        apellido: <?= json_encode($_SESSION['apellido'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        perfil: <?= json_encode($_SESSION['perfil_nombre'] ?? '', JSON_UNESCAPED_UNICODE) ?>
    });
</script>
<?php if (!empty($scriptsExtra)) foreach ($scriptsExtra as $src): ?>
<script src="<?= h(asset_url($src)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
