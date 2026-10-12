<?php
require_once __DIR__ . '/../models/TablaMaestra.php';

class MaestroController
{
    public const CONFIG_MAESTROS = [
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

    public static function manejar(): void
    {
        $tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? '';
        if (!isset(self::CONFIG_MAESTROS[$tipo])) {
            http_response_code(404);
            die('Tabla maestra no encontrada.');
        }
        $config = self::CONFIG_MAESTROS[$tipo];

        requerir_modulo($config['modulo']);
        $puedeCrear = tiene_permiso($config['modulo'], 'crear');
        $puedeEditar = tiene_permiso($config['modulo'], 'editar');
        $puedeEliminar = tiene_permiso($config['modulo'], 'eliminar');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::procesarAccion($tipo, $config, $puedeCrear, $puedeEditar, $puedeEliminar);
        }

        renderizar_vista('maestro/index', [
            'tituloPagina'      => $config['titulo'],
            'claveModuloActual' => $config['modulo'],
            'tipo'              => $tipo,
            'config'            => $config,
            'registros'         => TablaMaestra::listar($config['tabla']),
            'puedeCrear'        => $puedeCrear,
            'puedeEditar'       => $puedeEditar,
            'puedeEliminar'     => $puedeEliminar,
        ]);
    }

    private static function volver(string $tipo, bool $ok, string $mensaje): never
    {
        redirigir('maestro.php?tipo=' . urlencode($tipo) . '&' . ($ok ? 'ok' : 'error') . '=1&msg=' . urlencode($mensaje));
    }

    private static function procesarAccion(string $tipo, array $config, bool $puedeCrear, bool $puedeEditar, bool $puedeEliminar): void
    {
        $accion = $_POST['accion'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);

        if (in_array($accion, ['crear', 'editar'], true)) {
            $puedeGuardar = $accion === 'crear' ? $puedeCrear : $puedeEditar;
            if (!$puedeGuardar) self::volver($tipo, false, 'No tenés permiso para esta acción.');

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
                self::volver($tipo, false, 'Revisá los datos: ' . implode(' ', $errores));
            }

            if ($accion === 'crear') {
                $nuevoId = TablaMaestra::crear($config['tabla'], $datos);
                registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'CREAR', $config['tabla'], $nuevoId, "Alta en {$config['titulo']}");
                self::volver($tipo, true, $config['titulo'] . ': registro creado.');
            } else {
                TablaMaestra::actualizar($config['tabla'], $id, $datos);
                registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', $config['tabla'], $id, "Edición en {$config['titulo']} #$id");
                self::volver($tipo, true, $config['titulo'] . ': registro actualizado.');
            }
        }

        if ($accion === 'baja' && $puedeEliminar) {
            TablaMaestra::darBaja($config['tabla'], $id);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'BAJA', $config['tabla'], $id, "Baja lógica en {$config['titulo']} #$id");
            self::volver($tipo, true, 'Registro dado de baja.');
        }

        if ($accion === 'reactivar' && $puedeEliminar) {
            TablaMaestra::reactivar($config['tabla'], $id);
            registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'EDITAR', $config['tabla'], $id, "Reactivación en {$config['titulo']} #$id");
            self::volver($tipo, true, 'Registro reactivado.');
        }

        self::volver($tipo, false, 'Acción no permitida.');
    }
}
