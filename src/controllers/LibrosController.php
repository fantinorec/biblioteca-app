<?php
require_once __DIR__ . '/../models/Libro.php';
require_once __DIR__ . '/../models/TablaMaestra.php';

class LibrosController
{
    public static function manejar(): void
    {
        requerir_modulo('libros');

        $puedeCrear = tiene_permiso('libros', 'crear');
        $puedeEditar = tiene_permiso('libros', 'editar');
        $puedeEliminar = tiene_permiso('libros', 'eliminar');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::procesarAccion($puedeCrear, $puedeEditar, $puedeEliminar);
        }

        $soloLectura = !$puedeCrear && !$puedeEditar && !$puedeEliminar;

        if ($soloLectura) {
            renderizar_vista('libros/catalogo_lector', [
                'tituloPagina'      => 'Catálogo',
                'claveModuloActual' => 'libros',
                'libros'            => Libro::listar('activo'),
                'autores'           => TablaMaestra::listar('autores', 'activo'),
                'categorias'        => TablaMaestra::listar('categorias', 'activo'),
                'puedeCrearPrestamo' => tiene_permiso('prestamos', 'crear'),
            ]);
            return;
        }

        renderizar_vista('libros/index', [
            'tituloPagina'      => 'Libros',
            'claveModuloActual' => 'libros',
            'libros'            => Libro::listar(),
            'autores'           => TablaMaestra::listar('autores', 'activo'),
            'categorias'        => TablaMaestra::listar('categorias', 'activo'),
            'editoriales'       => TablaMaestra::listar('editoriales', 'activo'),
            'puedeCrear'        => $puedeCrear,
            'puedeEditar'       => $puedeEditar,
            'puedeEliminar'     => $puedeEliminar,
        ]);
    }

    private static function volver(bool $ok, string $mensaje): never
    {
        redirigir('libros.php?' . ($ok ? 'ok' : 'error') . '=1&msg=' . urlencode($mensaje));
    }

    private static function procesarAccion(bool $puedeCrear, bool $puedeEditar, bool $puedeEliminar): void
    {
        $accion = $_POST['accion'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);

        if (in_array($accion, ['crear', 'editar'], true)) {
            if (!($accion === 'crear' ? $puedeCrear : $puedeEditar)) {
                self::volver(false, 'No tenés permiso para esta acción.');
            }

            $datos = [
                'titulo'            => trim($_POST['titulo'] ?? ''),
                'isbn'              => trim($_POST['isbn'] ?? ''),
                'autor_id'          => (int) ($_POST['autor_id'] ?? 0),
                'categoria_id'      => (int) ($_POST['categoria_id'] ?? 0),
                'editorial_id'      => (int) ($_POST['editorial_id'] ?? 0),
                'anio_publicacion'  => trim($_POST['anio_publicacion'] ?? ''),
                'stock_total'       => (int) ($_POST['stock_total'] ?? 1),
                'imagen_url'        => trim($_POST['imagen_url'] ?? ''),
            ];

            $errores = validar_campos_requeridos($datos, [
                'titulo' => 'Título', 'autor_id' => 'Autor', 'categoria_id' => 'Categoría', 'editorial_id' => 'Editorial',
            ]);
            if ($datos['stock_total'] < 1) {
                $errores['stock_total'] = 'El stock debe ser al menos 1.';
            }
            if (empty($errores) && $datos['isbn'] !== '' && Libro::existeIsbn($datos['isbn'], $accion === 'editar' ? $id : null)) {
                $errores['isbn'] = 'Ya existe un libro con ese ISBN.';
            }
            if (empty($errores) && $datos['imagen_url'] !== '' && !preg_match('#^https?://#i', $datos['imagen_url'])) {
                $errores['imagen_url'] = 'La URL de la imagen debe empezar con http:// o https://';
            }
            if (!empty($errores)) {
                self::volver(false, 'Revisá los datos: ' . implode(' ', $errores));
            }

            if ($accion === 'crear') {
                $nuevoId = Libro::crear($datos);
                registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'CREAR', 'libros', $nuevoId, "Alta de libro \"{$datos['titulo']}\"");
                self::volver(true, 'Libro creado correctamente.');
            } else {
                Libro::actualizar($id, $datos);
                registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', 'libros', $id, "Edición de libro #$id");
                self::volver(true, 'Libro actualizado correctamente.');
            }
        }

        if ($accion === 'baja' && $puedeEliminar) {
            Libro::darBaja($id);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'BAJA', 'libros', $id, "Baja lógica de libro #$id");
            self::volver(true, 'Libro dado de baja.');
        }

        if ($accion === 'reactivar' && $puedeEliminar) {
            Libro::reactivar($id);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', 'libros', $id, "Reactivación de libro #$id");
            self::volver(true, 'Libro reactivado.');
        }

        self::volver(false, 'Acción no permitida.');
    }
}
