<?php
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/Libro.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Calificacion.php';

/**
 * Gestion de prestamos: solicitud/alta (de uno o varios libros a la vez,
 * incluso varias copias de un mismo titulo), listado (propio o completo),
 * aprobacion/rechazo de solicitudes y devoluciones. Cuando se pide o presta
 * mas de un ejemplar junto, todos esos renglones viajan atados por el mismo
 * grupo_id y la vista los muestra y gestiona como un unico "prestamo
 * multiple".
 */
class PrestamosController
{
    public static function manejar(): void
    {
        requerir_modulo('prestamos');
        Prestamo::actualizarVencidos();

        $puedeCrear = tiene_permiso('prestamos', 'crear');
        $puedeGestionarTodos = tiene_permiso('prestamos', 'editar'); // bibliotecario/admin: ve todos, aprueba/rechaza y marca devoluciones

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::procesarAccion($puedeCrear, $puedeGestionarTodos);
        }

        // Solo tiene sentido buscar cuando se ven TODOS los prestamos (un
        // lector ya ve nada mas que los suyos, no hay nada que buscar ahi).
        $busqueda = $puedeGestionarTodos ? trim($_GET['buscar'] ?? '') : '';

        $prestamos = $puedeGestionarTodos
            ? Prestamo::listar(null, null, $busqueda)
            : Prestamo::listar(null, (int) $_SESSION['usuario_id']);
        $librosDisponibles = array_filter(Libro::listar('activo'), fn($l) => (int) $l['stock_disponible'] > 0);
        $usuariosActivos = $puedeGestionarTodos ? Usuario::listar('activo') : [];

        renderizar_vista('prestamos/index', [
            'tituloPagina'         => 'Préstamos',
            'claveModuloActual'    => 'prestamos',
            'puedeCrear'           => $puedeCrear,
            'puedeGestionarTodos'  => $puedeGestionarTodos,
            'prestamos'            => self::agruparParaVista($prestamos),
            'librosDisponibles'    => $librosDisponibles,
            'usuariosActivos'      => $usuariosActivos,
            'busqueda'             => $busqueda,
        ]);
    }

    /**
     * Junta en un solo renglón los préstamos que comparten grupo_id (se
     * pidieron/prestaron varios libros, o varias copias de uno, juntos),
     * para que la vista los muestre y los gestione como una sola unidad.
     * $fila['libros'] queda con "título => cantidad" de ese grupo.
     *
     * @param array<int,array<string,mixed>> $filas ya vienen ordenadas por pr.id DESC
     * @return array<int,array<string,mixed>>
     */
    private static function agruparParaVista(array $filas): array
    {
        $grupos = [];
        $orden = [];
        foreach ($filas as $fila) {
            $clave = $fila['grupo_id'] ? ('g' . $fila['grupo_id']) : ('solo' . $fila['id']);
            if (!isset($grupos[$clave])) {
                $grupos[$clave] = $fila;
                $grupos[$clave]['libros'] = []; // titulo => cantidad
                $grupos[$clave]['filas'] = [];  // [{id, titulo}] cada ejemplar individual del grupo (para marcar el estado de devolución por separado)
                $orden[] = $clave;
            }
            $titulo = $fila['libro_titulo'];
            $grupos[$clave]['libros'][$titulo] = ($grupos[$clave]['libros'][$titulo] ?? 0) + 1;
            $grupos[$clave]['filas'][] = ['id' => (int) $fila['id'], 'titulo' => $titulo];
        }
        return array_map(fn($clave) => $grupos[$clave], $orden);
    }

    private static function volver(bool $ok, string $mensaje): never
    {
        redirigir('prestamos.php?' . ($ok ? 'ok' : 'error') . '=1&msg=' . urlencode($mensaje));
    }

    private static function procesarAccion(bool $puedeCrear, bool $puedeGestionarTodos): void
    {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'crear' && $puedeCrear) {
            // $_POST['libros'] viene como libro_id => cantidad (uno de los
            // inputs numericos del modal). Descartamos ids o cantidades invalidas aca;
            // Prestamo::registrar()/solicitar() vuelven a limpiar por las dudas.
            $librosSeleccionados = [];
            foreach ((array) ($_POST['libros'] ?? []) as $libroId => $cantidad) {
                $libroId = (int) $libroId;
                $cantidad = (int) $cantidad;
                if ($libroId > 0 && $cantidad > 0) {
                    $librosSeleccionados[$libroId] = $cantidad;
                }
            }
            $observaciones = trim($_POST['observaciones'] ?? '');

            // Si el perfil no gestiona todos los prestamos (es un Lector), solo puede pedir para si mismo.
            $usuarioId = $puedeGestionarTodos ? (int) ($_POST['usuario_id'] ?? 0) : (int) $_SESSION['usuario_id'];

            $errores = validar_campos_requeridos(['usuario_id' => $usuarioId], ['usuario_id' => 'Usuario']);
            if (empty($librosSeleccionados)) {
                $errores['libros'] = 'Elegí al menos un libro (con cantidad mayor a cero).';
            }
            if (!empty($errores)) {
                self::volver(false, 'Revisá los datos: ' . implode(' ', $errores));
            }

            // Tope de libros según la calificación del lector (estrellas): lo que ya
            // tiene pendiente/prestado/vencido, más lo que está pidiendo ahora.
            if ($usuarioId > 0) {
                $yaComprometidos = Prestamo::cantidadComprometidaDeUsuario($usuarioId);
                $pedidosAhora = array_sum($librosSeleccionados);
                $limite = Calificacion::limitePrestamos($usuarioId);
                if ($yaComprometidos + $pedidosAhora > $limite) {
                    $disponible = max(0, $limite - $yaComprometidos);
                    self::volver(false, "El lector tiene una calificación de $limite " . ($limite === 1 ? 'estrella' : 'estrellas')
                        . ", así que puede tener hasta $limite libro" . ($limite === 1 ? '' : 's') . " a la vez (entre pendientes y prestados). "
                        . "Ya tiene $yaComprometidos, así que puede pedir como máximo $disponible más.");
                }
            }

            try {
                if ($puedeGestionarTodos) {
                    // Alta directa: un bibliotecario/administrador presta el/los libro(s) en el momento.
                    // El plazo de devolución es fijo (3 meses desde hoy) para todos.
                    $ids = Prestamo::registrar($usuarioId, $librosSeleccionados, $_SESSION['usuario_id'], $observaciones);
                    $grupo = Prestamo::obtenerGrupoDetallado($ids[0]);
                    registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'PRESTAR', 'prestamos', $ids[0], self::mensajePrestamo($grupo));
                    $cantidad = count($ids);
                    $texto = $cantidad > 1 ? "Préstamo de $cantidad libros registrado correctamente." : 'Préstamo registrado correctamente.';
                    self::volver(true, $texto . ' Vence el ' . $grupo[0]['fecha_devolucion_estimada'] . '.');
                } else {
                    // El lector solo puede solicitar: queda(n) pendiente(s) hasta que un bibliotecario/administrador los apruebe.
                    $ids = Prestamo::solicitar($usuarioId, $librosSeleccionados, $observaciones);
                    $grupo = Prestamo::obtenerGrupoDetallado($ids[0]);
                    registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'SOLICITAR', 'prestamos', $ids[0], self::mensajeSolicitud($grupo));
                    $cantidad = count($ids);
                    $texto = $cantidad > 1 ? "Tu solicitud de $cantidad libros fue enviada." : 'Tu solicitud fue enviada.';
                    self::volver(true, $texto . ' Un bibliotecario la va a revisar pronto.');
                }
            } catch (RuntimeException $e) {
                self::volver(false, $e->getMessage());
            }
        }

        if ($accion === 'aprobar' && $puedeGestionarTodos) {
            $id = (int) ($_POST['id'] ?? 0);
            try {
                Prestamo::aprobar($id, (int) $_SESSION['usuario_id']);
                $grupo = Prestamo::obtenerGrupoDetallado($id);
                registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'PRESTAR', 'prestamos', $id, self::mensajePrestamo($grupo));
                $cantidad = count($grupo);
                $texto = $cantidad > 1
                    ? "Solicitud de $cantidad libros aprobada. Se descontó el stock de cada uno."
                    : 'Solicitud aprobada. Se descontó el stock del libro.';
                self::volver(true, $texto . ' Vence el ' . $grupo[0]['fecha_devolucion_estimada'] . '.');
            } catch (RuntimeException $e) {
                self::volver(false, $e->getMessage());
            }
        }

        if ($accion === 'rechazar' && $puedeGestionarTodos) {
            $id = (int) ($_POST['id'] ?? 0);
            try {
                $grupo = Prestamo::obtenerGrupoDetallado($id);
                Prestamo::rechazar($id, (int) $_SESSION['usuario_id']);
                registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'RECHAZAR', 'prestamos', $id, self::mensajeRechazo($grupo));
                self::volver(true, count($grupo) > 1 ? 'Solicitud de ' . count($grupo) . ' libros rechazada.' : 'Solicitud rechazada.');
            } catch (RuntimeException $e) {
                self::volver(false, $e->getMessage());
            }
        }

        if ($accion === 'devolver' && $puedeGestionarTodos) {
            $id = (int) ($_POST['id'] ?? 0);
            // $_POST['estado_libro'] viene como prestamo_id => "bueno"|"danado"
            // (un selector por cada ejemplar del grupo, no uno solo para todos).
            $estadoPorFila = [];
            foreach ((array) ($_POST['estado_libro'] ?? []) as $filaId => $estado) {
                $filaId = (int) $filaId;
                if ($filaId > 0) {
                    $estadoPorFila[$filaId] = $estado === 'danado' ? 'danado' : 'bueno';
                }
            }
            try {
                Prestamo::registrarDevolucion($id, $estadoPorFila);
                $grupo = Prestamo::obtenerGrupoDetallado($id);
                $cantidadDanados = count(array_filter($estadoPorFila, fn($e) => $e === 'danado'));
                registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'DEVOLVER', 'prestamos', $id, self::mensajeDevolucion($grupo, $cantidadDanados));
                $cantidad = count($grupo);
                $texto = $cantidad > 1
                    ? "Devolución registrada para los $cantidad libros. Se repuso el stock de cada uno."
                    : 'Devolución registrada. Se repuso el stock del libro.';
                if ($cantidadDanados > 0) {
                    $texto .= $cantidadDanados > 1
                        ? " Se registraron $cantidadDanados libros en mal estado: se descontaron $cantidadDanados estrellas de la calificación del lector."
                        : ' Se registró 1 libro en mal estado: se descontó 1 estrella de la calificación del lector.';
                }
                self::volver(true, $texto);
            } catch (RuntimeException $e) {
                self::volver(false, $e->getMessage());
            }
        }

        if ($accion === 'cancelar' && $puedeGestionarTodos) {
            $id = (int) ($_POST['id'] ?? 0);
            try {
                // Guardamos el estado y los datos ANTES de cancelar, para poder
                // decir en la auditoria que decision se deshizo (aprobacion o rechazo).
                $grupo = Prestamo::obtenerGrupoDetallado($id);
                $estadoAnterior = $grupo[0]['estado'] ?? null;
                Prestamo::cancelar($id);
                registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'CANCELAR', 'prestamos', $id, self::mensajeCancelacion($grupo, $estadoAnterior));
                $cantidad = count($grupo);
                $texto = $cantidad > 1
                    ? "Se canceló la decisión: los $cantidad libros vuelven a quedar pendientes."
                    : 'Se canceló la decisión: la solicitud vuelve a quedar pendiente.';
                self::volver(true, $texto);
            } catch (RuntimeException $e) {
                self::volver(false, $e->getMessage());
            }
        }

        self::volver(false, 'Acción no permitida.');
    }

    /** @param array<string,mixed>|null $p */
    private static function nombreLector(?array $p): string
    {
        if (!$p) {
            return 'un usuario';
        }
        return trim($p['usuario_nombre'] . ' ' . $p['usuario_apellido']) . ' (' . $p['usuario_email'] . ')';
    }

    /** Arma la lista de títulos de un grupo, agrupando copias repetidas como "Título x3". */
    private static function listaLibros(array $grupo): string
    {
        $cantidadPorTitulo = [];
        foreach ($grupo as $item) {
            $titulo = $item['libro_titulo'];
            $cantidadPorTitulo[$titulo] = ($cantidadPorTitulo[$titulo] ?? 0) + 1;
        }
        $partes = [];
        foreach ($cantidadPorTitulo as $titulo => $cantidad) {
            $partes[] = '"' . $titulo . '"' . ($cantidad > 1 ? ' x' . $cantidad : '');
        }
        return implode(', ', $partes);
    }

    /** @param array<int,array<string,mixed>> $grupo */
    private static function mensajePrestamo(array $grupo): string
    {
        if (empty($grupo)) {
            return 'Préstamo otorgado.';
        }
        $primero = $grupo[0];
        $cantidad = count($grupo);
        $descripcionLibros = $cantidad > 1
            ? ($cantidad . ' libros: ' . self::listaLibros($grupo))
            : ('el libro ' . self::listaLibros($grupo));
        return 'Préstamo de ' . $descripcionLibros . ' a ' . self::nombreLector($primero) . ', vence el ' . $primero['fecha_devolucion_estimada'] . '.';
    }

    /** @param array<int,array<string,mixed>> $grupo */
    private static function mensajeSolicitud(array $grupo): string
    {
        if (empty($grupo)) {
            return 'Solicitud de préstamo registrada.';
        }
        $primero = $grupo[0];
        $cantidad = count($grupo);
        $descripcionLibros = $cantidad > 1
            ? ($cantidad . ' libros: ' . self::listaLibros($grupo))
            : ('del libro ' . self::listaLibros($grupo));
        return 'Solicitud ' . $descripcionLibros . ' por ' . self::nombreLector($primero) . '.';
    }

    /** @param array<int,array<string,mixed>> $grupo */
    private static function mensajeRechazo(array $grupo): string
    {
        if (empty($grupo)) {
            return 'Solicitud de préstamo rechazada.';
        }
        $primero = $grupo[0];
        $cantidad = count($grupo);
        $descripcionLibros = $cantidad > 1
            ? ($cantidad . ' libros (' . self::listaLibros($grupo) . ')')
            : self::listaLibros($grupo);
        return 'Solicitud de ' . $descripcionLibros . ' pedida por ' . self::nombreLector($primero) . ' fue rechazada.';
    }

    /** @param array<int,array<string,mixed>> $grupo */
    private static function mensajeDevolucion(array $grupo, int $cantidadDanados = 0): string
    {
        if (empty($grupo)) {
            return 'Devolución registrada.';
        }
        $primero = $grupo[0];
        $cantidad = count($grupo);
        $descripcionLibros = $cantidad > 1
            ? ($cantidad . ' libros (' . self::listaLibros($grupo) . ')')
            : self::listaLibros($grupo);
        $sufijoEstado = $cantidadDanados > 0
            ? (', ' . $cantidadDanados . ($cantidadDanados > 1 ? ' dañados' : ' dañado'))
            : '';
        return 'Devuelto ' . $descripcionLibros . ' por ' . self::nombreLector($primero) . $sufijoEstado . '.';
    }

    /** @param array<int,array<string,mixed>> $grupo */
    private static function mensajeCancelacion(array $grupo, ?string $estadoAnterior): string
    {
        if (empty($grupo)) {
            return 'Se deshizo una decisión sobre un préstamo; vuelve a quedar pendiente.';
        }
        $primero = $grupo[0];
        $accionDeshecha = $estadoAnterior === 'prestado' ? 'la aprobación' : 'el rechazo';
        $cantidad = count($grupo);
        $descripcionLibros = $cantidad > 1
            ? ($cantidad . ' libros (' . self::listaLibros($grupo) . ')')
            : self::listaLibros($grupo);
        return 'Se deshizo ' . $accionDeshecha . ' de ' . $descripcionLibros . ' pedido por ' . self::nombreLector($primero) . '; vuelve a quedar pendiente.';
    }
}
