    </main>
</div>

<script src="assets/js/toast.js"></script>
<script src="assets/js/modal.js"></script>
<script src="assets/js/session.js"></script>
<script src="assets/js/validaciones.js"></script>
<script>
    // Sincroniza el cache local (localStorage) con la sesion real de PHP
    // en cada carga de pantalla interna, para que el nombre de arriba a
    // la derecha este siempre al dia (por ejemplo, justo despues de loguearse).
    guardarSesionLocal({
        nombre: <?= json_encode($_SESSION['nombre'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        apellido: <?= json_encode($_SESSION['apellido'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        perfil: <?= json_encode($_SESSION['perfil_nombre'] ?? '', JSON_UNESCAPED_UNICODE) ?>
    });
</script>
<?php if (!empty($scriptsExtra)) foreach ($scriptsExtra as $src): ?>
<script src="<?= h($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>
