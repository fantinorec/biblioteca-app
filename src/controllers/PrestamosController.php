<?php
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/Libro.php';
require_once __DIR__ . '/../models/Usuario.php';

class PrestamosController
{
    public static function manejar(): void
    {
        requerir_modulo('prestamos');
        Prestamo::actualizarVencidos();

        $puedeCrear = tiene_permiso('prestamos', 'crear');
        $puedeGestionarTodos = tiene_permiso('prestamos', 'editar');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::procesarAccion($puedeCrear, $puedeGestionarTodos);
        }

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
            'prestamos'            => $prestamos,
            'librosDisponibles'    => $librosDisponibles,
            'usuariosActivos'      => $usuariosActivos,
            'busqueda'             => $busqueda,
        ]);
    }

    private static function volver(bool $ok, string $mensaje): never
    {
        redirigir('prestamos.php?' . ($ok ? 'ok' : 'error') . '=1&msg=' . urlencode($mensaje));
    }

    private static function procesarAccion(bool $puedeCrear, bool $puedeGestionarTodos): void
    {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'crear' && $puedeCrear) {
            $libroId = (int) ($_POST['libro_id'] ?? 0);
            $observaciones = trim($_POST['observaciones'] ?? '');

            $usuarioId = $puedeGestionarTodos ? (int) ($_POST['usuario_id'] ?? 0) : (int) $_SESSION['usuario_id'];

            $errores = validar_campos_requeridos(
                ['libro_id' => $libroId, 'usuario_id' => $usuarioId],
                ['libro_id' => 'Libro', 'usuario_id' => 'Usuario']
            );
            if (!empty($errores)) {
                self::volver(false, 'Revisá los datos: ' . implode(' ', $errores));
            }

            try {
                if ($puedeGestionarTodos) {
                    $id = Prestamo::registrar($usuarioId, $libroId, $_SESSION['usuario_id'], $observaciones);
                    $detalle = Prestamo::obtenerDetallado($id);
                    registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'PRESTAR', 'prestamos', $id, self::mensajePrestamo($detalle));
                    self::volver(true, 'Préstamo registrado correctamente. Vence el ' . $detalle['fecha_devolucion_estimada'] . '.');
                } else {
                    $id = Prestamo::solicitar($usuarioId, $libroId, $observaciones);
                    $detalle = Prestamo::obtenerDetallado($id);
                    registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'SOLICITAR', 'prestamos', $id, self::mensajeSolicitud($detalle));
                    self::volver(true, 'Tu solicitud fue enviada. Un bibliotecario la va a revisar pronto.');
                }
            } catch (RuntimeException $e) {
                self::volver(false, $e->getMessage());
            }
        }

        if ($accion === 'aprobar' && $puedeGestionarTodos) {
            $id = (int) ($_POST['id'] ?? 0);
            try {
                Prestamo::aprobar($id, (int) $_SESSION['usuario_id']);
                $detalle = Prestamo::obtenerDetallado($id);
                registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'PRESTAR', 'prestamos', $id, self::mensajePrestamo($detalle));
                self::volver(true, 'Solicitud aprobada. Se descontó el stock del libro. Vence el ' . $detalle['fecha_devolucion_estimada'] . '.');
            } catch (RuntimeException $e) {
                self::volver(false, $e->getMessage());
            }
        }

        if ($accion === 'rechazar' && $puedeGestionarTodos) {
            $id = (int) ($_POST['id'] ?? 0);
            try {
                $detalle = Prestamo::obtenerDetallado($id);
                Prestamo::rechazar($id, (int) $_SESSION['usuario_id']);
                registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'RECHAZAR', 'prestamos', $id, self::mensajeRechazo($detalle));
                self::volver(true, 'Solicitud rechazada.');
            } catch (RuntimeException $e) {
                self::volver(false, $e->getMessage());
            }
        }

        if ($accion === 'devolver' && $puedeGestionarTodos) {
            $id = (int) ($_POST['id'] ?? 0);
            try {
                Prestamo::registrarDevolucion($id);
                $detalle = Prestamo::obtenerDetallado($id);
                registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'DEVOLVER', 'prestamos', $id, self::mensajeDevolucion($detalle));
                self::volver(true, 'Devolución registrada. Se repuso el stock del libro.');
            } catch (RuntimeException $e) {
                self::volver(false, $e->getMessage());
            }
        }

        if ($accion === 'cancelar' && $puedeGestionarTodos) {
            $id = (int) ($_POST['id'] ?? 0);
            try {
                $detalle = Prestamo::obtenerDetallado($id);
                $estadoAnterior = $detalle['estado'] ?? null;
                Prestamo::cancelar($id);
                registrar_auditoria($_SESSION['usuario_id'], $_SESSION['email'], 'CANCELAR', 'prestamos', $id, self::mensajeCancelacion($detalle, $estadoAnterior));
                self::volver(true, 'Se canceló la decisión: la solicitud volvió a quedar pendiente.');
            } catch (RuntimeException $e) {
                self::volver(false, $e->getMessage());
            }
        }

        self::volver(false, 'Acción no permitida.');
    }

    private static function nombreLector(?array $p): string
    {
        if (!$p) {
            return 'un usuario';
        }
        return trim($p['usuario_nombre'] . ' ' . $p['usuario_apellido']) . ' (' . $p['usuario_email'] . ')';
    }

    private static function mensajePrestamo(?array $p): string
    {
        if (!$p) {
            return 'Préstamo otorgado.';
        }
        return "Libro \"{$p['libro_titulo']}\" prestado a " . self::nombreLector($p) . ", vence el {$p['fecha_devolucion_estimada']}.";
    }

    private static function mensajeSolicitud(?array $p): string
    {
        if (!$p) {
            return 'Solicitud de préstamo registrada.';
        }
        return "Solicitud del libro \"{$p['libro_titulo']}\" por " . self::nombreLector($p) . '.';
    }

    private static function mensajeRechazo(?array $p): string
    {
        if (!$p) {
            return 'Solicitud de préstamo rechazada.';
        }
        return "Solicitud del libro \"{$p['libro_titulo']}\" pedida por " . self::nombreLector($p) . ' fue rechazada.';
    }

    private static function mensajeDevolucion(?array $p): string
    {
        if (!$p) {
            return 'Devolución registrada.';
        }
        return "Libro \"{$p['libro_titulo']}\" devuelto por " . self::nombreLector($p) . '.';
    }

    private static function mensajeCancelacion(?array $p, ?string $estadoAnterior): string
    {
        if (!$p) {
            return 'Se deshizo una decisión sobre un préstamo; vuelve a quedar pendiente.';
        }
        $accionDeshecha = $estadoAnterior === 'prestado' ? 'la aprobación' : 'el rechazo';
        return "Se deshizo {$accionDeshecha} del libro \"{$p['libro_titulo']}\" pedido por " . self::nombreLector($p) . '; vuelve a quedar pendiente.';
    }
}
