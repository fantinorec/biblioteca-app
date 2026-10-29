<?php

/**
 * Calificación por estrellas de cada lector: arranca en 5, baja por
 * devoluciones tardías o libros dañados, y se recupera sola con buena
 * conducta (3 devoluciones seguidas sin problemas). Queda un historial con
 * el motivo de cada cambio, para poder mostrárselo al lector si reclama.
 */
class Calificacion
{
    private const MINIMO = 1;
    private const MAXIMO = 5;
    private const RACHA_PARA_SUBIR = 3;

    /**
     * Se llama desde Prestamo::registrarDevolucion(), DENTRO de la misma
     * transacción (recibe el $pdo ya abierto y con la fila del usuario
     * bloqueable): aplica la penalización que corresponda (atraso y/o mal
     * estado) o, si la devolución fue impecable, suma a la racha de buena
     * conducta y sube la calificación si corresponde.
     *
     * $librosDanados es la lista de TÍTULOS de los ejemplares que volvieron
     * dañados (puede estar vacía si ninguno lo estaba). Si el préstamo era de
     * varios libros y solo uno volvió dañado, se descuenta una sola estrella
     * (una por cada ejemplar dañado, no por todo el grupo), más una estrella
     * extra si además la devolución llegó tarde.
     */
    public static function aplicarConsecuenciaDevolucion(PDO $pdo, int $usuarioId, ?int $prestamoReferenciaId, int $diasAtraso, array $librosDanados): void
    {
        $stmt = $pdo->prepare('SELECT calificacion, racha_buena FROM usuarios WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $usuarioId]);
        $usuario = $stmt->fetch();
        if (!$usuario) {
            return; // no debería pasar, pero por las dudas no rompemos la devolución.
        }

        $calificacionActual = (int) $usuario['calificacion'];

        $motivos = [];
        if ($diasAtraso > 0) {
            $motivos[] = $diasAtraso === 1
                ? 'Devolución tardía (1 día de atraso)'
                : "Devolución tardía ($diasAtraso días de atraso)";
        }
        foreach ($librosDanados as $titulo) {
            $motivos[] = 'Libro devuelto en mal estado: "' . $titulo . '"';
        }

        if (!empty($motivos)) {
            $nuevaCalificacion = max(self::MINIMO, $calificacionActual - count($motivos));
            $pdo->prepare('UPDATE usuarios SET calificacion = :calificacion, racha_buena = 0 WHERE id = :id')
                ->execute(['calificacion' => $nuevaCalificacion, 'id' => $usuarioId]);

            foreach ($motivos as $motivo) {
                self::registrarHistorial($pdo, $usuarioId, $prestamoReferenciaId, -1, $motivo);
            }
            return;
        }

        // Devolución impecable: suma a la racha y, cada 3 seguidas, sube 1 estrella (hasta 5).
        $nuevaRacha = (int) $usuario['racha_buena'] + 1;
        if ($nuevaRacha >= self::RACHA_PARA_SUBIR && $calificacionActual < self::MAXIMO) {
            $nuevaCalificacion = min(self::MAXIMO, $calificacionActual + 1);
            $pdo->prepare('UPDATE usuarios SET calificacion = :calificacion, racha_buena = 0 WHERE id = :id')
                ->execute(['calificacion' => $nuevaCalificacion, 'id' => $usuarioId]);
            self::registrarHistorial(
                $pdo,
                $usuarioId,
                $prestamoReferenciaId,
                1,
                'Subió por buena conducta (3 devoluciones seguidas a tiempo y en buen estado)'
            );
        } else {
            $pdo->prepare('UPDATE usuarios SET racha_buena = :racha WHERE id = :id')
                ->execute(['racha' => $nuevaRacha, 'id' => $usuarioId]);
        }
    }

    private static function registrarHistorial(PDO $pdo, int $usuarioId, ?int $prestamoId, int $delta, string $motivo): void
    {
        $pdo->prepare(
            'INSERT INTO historial_calificacion (usuario_id, prestamo_id, delta, motivo, fecha_creacion)
             VALUES (:usuario_id, :prestamo_id, :delta, :motivo, NOW())'
        )->execute([
            'usuario_id' => $usuarioId,
            'prestamo_id' => $prestamoId,
            'delta'      => $delta,
            'motivo'     => $motivo,
        ]);
    }

    /** Historial completo de un lector (más reciente primero), para la pantalla de Calificaciones. */
    public static function historialDeUsuario(int $usuarioId): array
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('SELECT * FROM historial_calificacion WHERE usuario_id = :id ORDER BY fecha_creacion DESC, id DESC');
        $stmt->execute(['id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    /** Cuántos libros puede tener prestados/pendientes a la vez un lector, según su calificación actual. */
    public static function limitePrestamos(int $usuarioId): int
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('SELECT calificacion FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $usuarioId]);
        $fila = $stmt->fetch();
        return $fila ? max(self::MINIMO, (int) $fila['calificacion']) : self::MAXIMO;
    }
}
