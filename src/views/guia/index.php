<?php
/**
 * Arma el numerito + la flecha que apunta a una parte de la captura.
 * ($cx,$cy) es donde va el circulo con el numero; ($tx,$ty), si se pasa,
 * es el punto exacto de la pantalla al que apunta la flecha.
 */
function guia_flecha(int $numero, int $cx, int $cy, ?int $tx = null, ?int $ty = null, string $marcador = 'puntaFlecha'): string
{
    $html = '';
    if ($tx !== null && $ty !== null) {
        $html .= '<line x1="' . $cx . '" y1="' . $cy . '" x2="' . $tx . '" y2="' . $ty . '" class="guia-flecha" marker-end="url(#' . $marcador . ')"></line>';
    }
    $html .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="15" class="guia-circulo"></circle>';
    $html .= '<text x="' . $cx . '" y="' . $cy . '" class="guia-numero">' . $numero . '</text>';
    return $html;
}

/** El <marker> (punta de flecha) de un SVG, con un id propio para no repetir ids en la pagina. */
function guia_marcador(string $id): string
{
    return '<marker id="' . $id . '" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
        <path d="M0,0 L10,5 L0,10 z" class="guia-punta"></path>
    </marker>';
}
?>
<?php require __DIR__ . '/../../partials/header.php'; ?>
<style>
    .guia-intro{max-width:760px;}
    .guia-seccion{margin-bottom:16px;}
    .guia-seccion summary{cursor:pointer;font-size:1.15rem;font-weight:700;padding:4px 0;}
    .guia-seccion summary::-webkit-details-marker{color:#7a8a3a;}
    .guia-cuerpo{margin-top:14px;display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start;}
    .guia-captura{position:relative;width:100%;max-width:420px;flex:0 0 auto;border:1px solid #d8cdb0;border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);}
    .guia-captura img{display:block;width:100%;height:auto;}
    .guia-captura svg{position:absolute;inset:0;width:100%;height:100%;}
    .guia-circulo{fill:#c0392b;stroke:#fff;stroke-width:2;}
    .guia-numero{fill:#fff;font-size:16px;font-weight:700;text-anchor:middle;dominant-baseline:central;font-family:inherit;}
    .guia-flecha{stroke:#c0392b;stroke-width:3;fill:none;}
    .guia-punta{fill:#c0392b;}
    .guia-leyenda{flex:1 1 260px;min-width:220px;margin:0;padding-left:0;list-style:none;}
    .guia-leyenda li{display:flex;gap:10px;margin-bottom:12px;align-items:flex-start;}
    .guia-leyenda li .guia-leyenda__num{flex:0 0 auto;width:26px;height:26px;border-radius:50%;background:#c0392b;color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center;font-size:.85rem;}
    .guia-leyenda li p{margin:2px 0 0;}
    .guia-leyenda li .badge{margin-top:2px;}
    .guia-aviso-rol{display:inline-block;margin-bottom:18px;padding:6px 14px;border-radius:999px;background:#eef1dd;color:#5b6b2a;font-weight:600;font-size:.9rem;}
</style>

<div class="toolbar">
    <h1 class="mb-0">Guía de uso</h1>
    <a href="index.php" class="btn btn-secundario">← Volver al inicio</a>
</div>
<p class="texto-suave guia-intro mb-1">
    Capturas reales del sistema con flechas que señalan qué hace cada botón, campo o indicador.
    Tocá cada título para abrir o cerrar esa sección.
</p>
<span class="guia-aviso-rol">
    Estás viendo la guía para tu perfil: <?= $puedeGestionarTodos ? 'Bibliotecario / Administrador' : 'Lector' ?>
</span>

<?php if ($puedeGestionarTodos): ?>

    <details class="card guia-seccion" open>
        <summary>1. El menú principal</summary>
        <div class="guia-cuerpo">
            <div class="guia-captura">
                <img src="<?= asset_url('assets/img/guia/guia-menu.jpg') ?>" alt="Menú lateral con todos los módulos del sistema">
                <svg viewBox="0 0 764 868" preserveAspectRatio="xMidYMid meet">
                    <defs><?= guia_marcador('pm1') ?></defs>
                    <?php
                    $modulosMenu = [
                        1  => 'Inicio', 2  => 'Usuarios', 3  => 'Perfiles', 4  => 'Permisos',
                        5  => 'Libros', 6  => 'Autores', 7  => 'Categorías', 8  => 'Editoriales',
                        9  => 'Préstamos', 10 => 'Reportes', 11 => 'Auditoría',
                    ];
                    $y = 167;
                    foreach ($modulosMenu as $numero => $nombre) {
                        echo guia_flecha($numero, 305, $y, 270, $y, 'pm1');
                        $y += 66;
                    }
                    ?>
                </svg>
            </div>
            <ol class="guia-leyenda">
                <li><span class="guia-leyenda__num">1</span><p><strong>Inicio</strong>: el panel con los indicadores generales (esta misma pantalla desde donde abrís la guía).</p></li>
                <li><span class="guia-leyenda__num">2</span><p><strong>Usuarios</strong>: alta, edición y baja de lectores/bibliotecarios/administradores.</p></li>
                <li><span class="guia-leyenda__num">3</span><p><strong>Perfiles</strong>: los "roles" que puede tener un usuario (Administrador, Lector, u otros que crees).</p></li>
                <li><span class="guia-leyenda__num">4</span><p><strong>Permisos</strong>: qué puede ver/crear/editar/eliminar cada perfil, módulo por módulo.</p></li>
                <li><span class="guia-leyenda__num">5</span><p><strong>Libros</strong>: el catálogo completo, con su stock disponible.</p></li>
                <li><span class="guia-leyenda__num">6</span><p><strong>Autores</strong>: tabla de apoyo para cargar libros (se elige al crear un libro).</p></li>
                <li><span class="guia-leyenda__num">7</span><p><strong>Categorías</strong>: idem, el género/temática del libro.</p></li>
                <li><span class="guia-leyenda__num">8</span><p><strong>Editoriales</strong>: idem, la editorial del libro.</p></li>
                <li><span class="guia-leyenda__num">9</span><p><strong>Préstamos</strong>: pedir, aprobar/rechazar y devolver préstamos (podés pedir varios libros a la vez).</p></li>
                <li><span class="guia-leyenda__num">10</span><p><strong>Reportes</strong>: gráficos y exportación a Excel/PDF.</p></li>
                <li><span class="guia-leyenda__num">11</span><p><strong>Auditoría</strong>: historial de todas las acciones importantes que se hicieron en el sistema.</p></li>
            </ol>
        </div>
    </details>

<?php else: ?>

    <details class="card guia-seccion" open>
        <summary>1. El menú principal</summary>
        <div class="guia-cuerpo">
            <div class="guia-captura">
                <img src="<?= asset_url('assets/img/guia/guia-menu-lector.jpg') ?>" alt="Menú lateral de un lector: Inicio, Libros y Préstamos">
                <svg viewBox="0 0 764 868" preserveAspectRatio="xMidYMid meet">
                    <defs><?= guia_marcador('pm1l') ?></defs>
                    <?= guia_flecha(1, 305, 167, 270, 167, 'pm1l') ?>
                    <?= guia_flecha(2, 305, 235, 270, 235, 'pm1l') ?>
                    <?= guia_flecha(3, 305, 301, 270, 301, 'pm1l') ?>
                </svg>
            </div>
            <ol class="guia-leyenda">
                <li><span class="guia-leyenda__num">1</span><p><strong>Inicio</strong>: el panel con los indicadores generales de la biblioteca.</p></li>
                <li><span class="guia-leyenda__num">2</span><p><strong>Libros</strong>: el catálogo para consultar (no lo podés editar, solo verlo y pedir préstamos).</p></li>
                <li><span class="guia-leyenda__num">3</span><p><strong>Préstamos</strong>: "Mis préstamos" — tus solicitudes y lo que tenés prestado.</p></li>
            </ol>
        </div>
        <p class="texto-suave" style="margin-top:14px;margin-bottom:0;">Como Lector no tenés acceso a Usuarios, Perfiles, Permisos, Reportes ni Auditoría: esos módulos los maneja un bibliotecario o administrador.</p>
    </details>

<?php endif; ?>

<details class="card guia-seccion">
    <summary>2. Pantalla de inicio</summary>
    <div class="guia-cuerpo">
        <div class="guia-captura">
            <img src="<?= asset_url('assets/img/guia/guia-inicio.jpg') ?>" alt="Pantalla de inicio con indicadores">
            <svg viewBox="0 0 764 868" preserveAspectRatio="xMidYMid meet">
                <defs><?= guia_marcador('pm2') ?></defs>
                <?= guia_flecha(1, 44, 78, 44, 42, 'pm2') ?>
                <?= guia_flecha(2, 460, 80, 460, 44, 'pm2') ?>
                <?= guia_flecha(3, 684, 80, 684, 44, 'pm2') ?>
                <?= guia_flecha(4, 700, 420, 650, 420, 'pm2') ?>
                <line x1="372" y1="815" x2="200" y2="710" class="guia-flecha" marker-end="url(#pm2)"></line>
                <line x1="372" y1="815" x2="545" y2="710" class="guia-flecha" marker-end="url(#pm2)"></line>
                <circle cx="372" cy="815" r="15" class="guia-circulo"></circle>
                <text x="372" y="815" class="guia-numero">5</text>
            </svg>
        </div>
        <ol class="guia-leyenda">
            <li><span class="guia-leyenda__num">1</span><p>Abre o cierra el menú de módulos.</p></li>
            <li><span class="guia-leyenda__num">2</span><p>Tu nombre y tu perfil (rol) actual.</p></li>
            <li><span class="guia-leyenda__num">3</span><p><strong>Salir</strong>: cierra tu sesión.</p></li>
            <li><span class="guia-leyenda__num">4</span><p><strong>Novedades</strong>: los últimos libros agregados al catálogo (se desliza solo; también podés tocar los puntitos de abajo).</p></li>
            <li><span class="guia-leyenda__num">5</span><p>Indicadores generales, se actualizan solos: libros activos, usuarios activos, préstamos vigentes y préstamos vencidos.</p></li>
        </ol>
    </div>
</details>

<?php if ($puedeGestionarTodos): ?>

    <details class="card guia-seccion">
        <summary>3. Catálogo de libros</summary>
        <div class="guia-cuerpo">
            <div class="guia-captura">
                <img src="<?= asset_url('assets/img/guia/guia-libros.jpg') ?>" alt="Catálogo de libros en vista de tabla">
                <svg viewBox="0 0 764 868" preserveAspectRatio="xMidYMid meet">
                    <defs><?= guia_marcador('pm3') ?></defs>
                    <?= guia_flecha(1, 629, 100, 629, 125, 'pm3') ?>
                    <?= guia_flecha(2, 170, 260, 107, 300, 'pm3') ?>
                    <?= guia_flecha(3, 300, 260, 230, 300, 'pm3') ?>
                    <?= guia_flecha(4, 700, 270, 650, 310, 'pm3') ?>
                </svg>
            </div>
            <ol class="guia-leyenda">
                <li><span class="guia-leyenda__num">1</span><p><strong>+ Nuevo libro</strong>: da de alta un título nuevo en el catálogo.</p></li>
                <li><span class="guia-leyenda__num">2</span><p>Portada del libro (si no tiene imagen cargada, se muestra un ícono genérico).</p></li>
                <li><span class="guia-leyenda__num">3</span><p>Título, autor, categoría y editorial del libro.</p></li>
                <li><span class="guia-leyenda__num">4</span><p><strong>Stock</strong>: ejemplares disponibles / total. Solo los libros con disponibles aparecen para prestar.</p></li>
            </ol>
        </div>
    </details>

    <details class="card guia-seccion">
        <summary>4. Préstamos: cómo se ven en la lista</summary>
        <div class="guia-cuerpo">
            <div class="guia-captura">
                <img src="<?= asset_url('assets/img/guia/guia-prestamos-lista.jpg') ?>" alt="Listado de préstamos, con un préstamo múltiple agrupado">
                <svg viewBox="0 0 764 868" preserveAspectRatio="xMidYMid meet">
                    <defs><?= guia_marcador('pm4') ?></defs>
                    <?= guia_flecha(1, 608, 100, 608, 125, 'pm4') ?>
                    <?= guia_flecha(2, 300, 220, 300, 250, 'pm4') ?>
                    <?= guia_flecha(3, 230, 440, 118, 465, 'pm4') ?>
                    <?= guia_flecha(4, 300, 555, 190, 555, 'pm4') ?>
                </svg>
            </div>
            <ol class="guia-leyenda">
                <li><span class="guia-leyenda__num">1</span><p><strong>+ Nuevo préstamo</strong>: abre el formulario para prestar uno o varios libros.</p></li>
                <li><span class="guia-leyenda__num">2</span><p>Buscador de préstamos por nombre, apellido o email del lector.</p></li>
                <li><span class="guia-leyenda__num">3</span><p>"<strong>Préstamo múltiple</strong>" indica que ese renglón junta varios libros (o varias copias) pedidos juntos. Se aprueban/devuelven siempre todos a la vez.</p></li>
                <li><span class="guia-leyenda__num">4</span><p>El "× 4" al lado de un título significa que son 4 ejemplares de ese mismo libro.</p></li>
            </ol>
        </div>
    </details>

    <details class="card guia-seccion">
        <summary>5. Préstamos: pedir uno nuevo (incluso varios libros a la vez)</summary>
        <div class="guia-cuerpo">
            <div class="guia-captura">
                <img src="<?= asset_url('assets/img/guia/guia-prestamos-nuevo.jpg') ?>" alt="Formulario de nuevo préstamo con cantidad por libro">
                <svg viewBox="0 0 764 868" preserveAspectRatio="xMidYMid meet">
                    <defs><?= guia_marcador('pm5') ?></defs>
                    <?= guia_flecha(1, 650, 156, 450, 156, 'pm5') ?>
                    <?= guia_flecha(2, 650, 291, 560, 291, 'pm5') ?>
                    <?= guia_flecha(3, 680, 595, 500, 580, 'pm5') ?>
                    <?= guia_flecha(4, 680, 700, 500, 685, 'pm5') ?>
                    <?= guia_flecha(5, 420, 820, 460, 784, 'pm5') ?>
                </svg>
            </div>
            <ol class="guia-leyenda">
                <li><span class="guia-leyenda__num">1</span><p><strong>Lector</strong>: a quién le prestás (buscá por nombre, apellido o email).</p></li>
                <li><span class="guia-leyenda__num">2</span><p>Cantidad de ese título: poné cuántos ejemplares querés (0 = no pedís ese libro). Podés poner cantidad en más de uno.</p></li>
                <li><span class="guia-leyenda__num">3</span><p>El plazo de devolución es fijo (3 meses) y, si pedís más de un ejemplar, se devuelven siempre todos juntos.</p></li>
                <li><span class="guia-leyenda__num">4</span><p>Observaciones (opcional): una nota libre sobre el préstamo.</p></li>
                <li><span class="guia-leyenda__num">5</span><p><strong>Registrar préstamo</strong>: confirma y descuenta el stock de cada libro elegido.</p></li>
            </ol>
        </div>
    </details>

    <details class="card guia-seccion">
        <summary>6. Préstamos: estados y botones de acción</summary>
        <div class="guia-cuerpo">
            <div class="guia-captura">
                <img src="<?= asset_url('assets/img/guia/guia-prestamos-estados.jpg') ?>" alt="Estados de préstamo y botones de aprobar, rechazar, devolver y cancelar">
                <svg viewBox="0 0 764 868" preserveAspectRatio="xMidYMid meet">
                    <defs><?= guia_marcador('pm6') ?></defs>
                    <?= guia_flecha(1, 340, 651, 420, 651, 'pm6') ?>
                    <?= guia_flecha(2, 680, 600, 620, 628, 'pm6') ?>
                    <?= guia_flecha(3, 680, 690, 620, 669, 'pm6') ?>
                    <?= guia_flecha(4, 340, 541, 420, 541, 'pm6') ?>
                    <?= guia_flecha(5, 690, 495, 630, 520, 'pm6') ?>
                    <?= guia_flecha(6, 690, 590, 620, 569, 'pm6') ?>
                    <?= guia_flecha(7, 340, 433, 420, 433, 'pm6') ?>
                </svg>
            </div>
            <ol class="guia-leyenda">
                <li><span class="guia-leyenda__num">1</span><p><strong>Pendiente</strong>: recién solicitado, todavía nadie lo aprobó ni rechazó.</p></li>
                <li><span class="guia-leyenda__num">2</span><p><strong>Aprobar</strong>: descuenta el stock de cada libro y pasa el préstamo a "prestado".</p></li>
                <li><span class="guia-leyenda__num">3</span><p><strong>Rechazar</strong>: rechaza la solicitud (no toca el stock).</p></li>
                <li><span class="guia-leyenda__num">4</span><p><strong>Prestado</strong>: el/los libro(s) están en préstamo; vencen en la fecha de "Devolución estimada".</p></li>
                <li><span class="guia-leyenda__num">5</span><p><strong>Registrar devolución</strong>: marca el préstamo como devuelto y repone el stock de cada libro.</p></li>
                <li><span class="guia-leyenda__num">6</span><p><strong>Cancelar</strong>: deshace la aprobación (vuelve a "pendiente" y repone el stock).</p></li>
                <li><span class="guia-leyenda__num">7</span><p><strong>Devuelto</strong>: ya se devolvió; no quedan acciones pendientes.</p></li>
            </ol>
        </div>
    </details>

<?php else: ?>

    <details class="card guia-seccion">
        <summary>3. Catálogo de libros</summary>
        <div class="guia-cuerpo">
            <div class="guia-captura">
                <img src="<?= asset_url('assets/img/guia/guia-libros-lector.jpg') ?>" alt="Catálogo de libros en tarjetas, con botón para pedir préstamo">
                <svg viewBox="0 0 764 868" preserveAspectRatio="xMidYMid meet">
                    <defs><?= guia_marcador('pm3l') ?></defs>
                    <?= guia_flecha(1, 117, 155, 117, 191, 'pm3l') ?>
                    <?= guia_flecha(2, 277, 155, 277, 191, 'pm3l') ?>
                    <?= guia_flecha(3, 113, 610, 113, 565, 'pm3l') ?>
                    <?= guia_flecha(4, 350, 610, 268, 566, 'pm3l') ?>
                </svg>
            </div>
            <ol class="guia-leyenda">
                <li><span class="guia-leyenda__num">1</span><p><strong>Categorías</strong>: filtra el catálogo por género/temática.</p></li>
                <li><span class="guia-leyenda__num">2</span><p><strong>Autores</strong>: filtra el catálogo por autor.</p></li>
                <li><span class="guia-leyenda__num">3</span><p><strong>Disponible</strong>: indica que hay al menos un ejemplar libre para prestar.</p></li>
                <li><span class="guia-leyenda__num">4</span><p><strong>Pedir préstamo</strong>: te lleva directo a Préstamos con ese libro ya elegido.</p></li>
            </ol>
        </div>
    </details>

    <details class="card guia-seccion">
        <summary>4. Préstamos: "Mis préstamos"</summary>
        <div class="guia-cuerpo">
            <div class="guia-captura">
                <img src="<?= asset_url('assets/img/guia/guia-prestamos-lista-lector.jpg') ?>" alt="Pantalla Mis préstamos de un lector">
                <svg viewBox="0 0 764 868" preserveAspectRatio="xMidYMid meet">
                    <defs><?= guia_marcador('pm4l') ?></defs>
                    <?= guia_flecha(1, 601, 100, 601, 125, 'pm4l') ?>
                    <?= guia_flecha(2, 365, 260, 365, 230, 'pm4l') ?>
                </svg>
            </div>
            <ol class="guia-leyenda">
                <li><span class="guia-leyenda__num">1</span><p><strong>+ Solicitar préstamo</strong>: pedí uno o varios libros a la vez (hasta varias copias de un mismo título).</p></li>
                <li><span class="guia-leyenda__num">2</span><p>Acá vas a ver todas tus solicitudes y préstamos, con su estado. Si pedís más de un libro junto, se aprueban y se devuelven siempre todos a la vez.</p></li>
            </ol>
        </div>
    </details>

    <details class="card guia-seccion">
        <summary>5. Préstamos: pedir uno nuevo (incluso varios libros a la vez)</summary>
        <div class="guia-cuerpo">
            <div class="guia-captura">
                <img src="<?= asset_url('assets/img/guia/guia-prestamos-nuevo-lector.jpg') ?>" alt="Formulario de solicitar préstamo con cantidad por libro">
                <svg viewBox="0 0 764 868" preserveAspectRatio="xMidYMid meet">
                    <defs><?= guia_marcador('pm5l') ?></defs>
                    <?= guia_flecha(1, 650, 243, 560, 243, 'pm5l') ?>
                    <?= guia_flecha(2, 680, 480, 500, 531, 'pm5l') ?>
                    <?= guia_flecha(3, 680, 600, 500, 648, 'pm5l') ?>
                    <?= guia_flecha(4, 420, 790, 460, 736, 'pm5l') ?>
                </svg>
            </div>
            <ol class="guia-leyenda">
                <li><span class="guia-leyenda__num">1</span><p>Cantidad de ese título: poné cuántos ejemplares querés (0 = no pedís ese libro). Podés poner cantidad en más de uno.</p></li>
                <li><span class="guia-leyenda__num">2</span><p>El plazo de devolución es fijo (3 meses) y, si pedís más de un ejemplar, tenés que devolverlos siempre todos juntos.</p></li>
                <li><span class="guia-leyenda__num">3</span><p>Observaciones (opcional): una nota libre sobre el pedido.</p></li>
                <li><span class="guia-leyenda__num">4</span><p><strong>Enviar solicitud</strong>: queda "pendiente" hasta que un bibliotecario o administrador la revise.</p></li>
            </ol>
        </div>
    </details>

    <details class="card guia-seccion">
        <summary>6. Qué significa cada estado de mi préstamo</summary>
        <ul class="guia-leyenda" style="margin-top:14px;">
            <li><span class="badge badge-pendiente">pendiente</span><p>Recién la pediste; un bibliotecario o administrador todavía no la revisó.</p></li>
            <li><span class="badge badge-prestado">prestado</span><p>Te la aprobaron: ya tenés el/los libro(s). Hay que devolverlo(s) antes de la fecha de "Devolución estimada".</p></li>
            <li><span class="badge badge-devuelto">devuelto</span><p>Ya devolviste el/los libro(s).</p></li>
            <li><span class="badge badge-rechazado">rechazado</span><p>El bibliotecario o administrador no aprobó la solicitud.</p></li>
            <li><span class="badge badge-vencido">vencido</span><p>Se pasó la fecha de devolución estimada y todavía no lo devolviste: devolvelo cuanto antes.</p></li>
        </ul>
        <p class="texto-suave" style="margin-top:4px;">Como Lector no tenés botones de acción acá: aprobar, rechazar y registrar la devolución lo hace un bibliotecario o administrador.</p>
    </details>

<?php endif; ?>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
