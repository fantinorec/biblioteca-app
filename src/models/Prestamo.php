<?php

class Prestamo
{
    private const MESES_PLAZO = 3;

    public static function calcularFechaDevolucionEstimada(): string
    {
        return date('Y-m-d', strtotime('+' . self::MESES_PLAZO . ' months'));
    }

    public static function listar(?string $estado = null, ?int $usuarioId = null, ?string $busqueda = null): array
    {
        $pdo = obtenerConexion();
        $sql = 'SELECT pr.*, l.titulo AS libro_titulo, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, u.email AS usuario_email
                FROM prestamos pr
                INNER JOIN libros l ON l.id = pr.libro_id
                INNER JOIN usuarios u ON u.id = pr.usuario_id
                WHERE 1 = 1';
        $params = [];
        if ($estado) {
            $sql .= ' AND pr.estado = :estado';
            $params['estado'] = $estado;
        }
        if ($usuarioId) {
            $sql .= ' AND pr.usuario_id = :usuario_id';
            $params['usuario_id'] = $usuarioId;
        }
        if ($busqueda !== null && $busqueda !== '') {
            $sql .= ' AND (u.nombre LIKE :busqueda_nombre
                           OR u.apellido LIKE :busqueda_apellido
                           OR CONCAT(u.nombre, " ", u.apellido) LIKE :busqueda_completo
                           OR u.email LIKE :busqueda_email)';
            $comodin = '%' . $busqueda . '%';
            $params['busqueda_nombre'] = $comodin;
            $params['busqueda_apellido'] = $comodin;
            $params['busqueda_completo'] = $comodin;
            $params['busqueda_email'] = $comodin;
        }
        $sql .= ' ORDER BY pr.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function buscarPorId(int $id): ?array
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare('SELECT * FROM prestamos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function obtenerDetallado(int $id): ?array
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT pr.*, l.titulo AS libro_titulo, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, u.email AS usuario_email
             FROM prestamos pr
             INNER JOIN libros l ON l.id = pr.libro_id
             INNER JOIN usuarios u ON u.id = pr.usuario_id
             WHERE pr.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Todas las filas que viajan "atadas" junto con $prestamoId: si pidieron/
     * prestaron varios libros (o varias copias del mismo libro) a la vez,
     * comparten el mismo grupo_id y se aprueban, rechazan, cancelan o
     * devuelven siempre todas juntas. Si el préstamo es viejo (de antes de
     * poder pedir varios libros juntos) y no tiene grupo_id, el "grupo" es
     * simplemente él mismo.
     *
     * @return array<int,array<string,mixed>> con libro_titulo, usuario_nombre, etc.
     */
    public static function obtenerGrupoDetallado(int $prestamoId): array
    {
        $pdo = obtenerConexion();
        $propio = self::buscarPorId($prestamoId);
        if (!$propio) {
            return [];
        }
        if ($propio['grupo_id']) {
            $stmt = $pdo->prepare(
                'SELECT pr.*, l.titulo AS libro_titulo, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, u.email AS usuario_email
                 FROM prestamos pr
                 INNER JOIN libros l ON l.id = pr.libro_id
                 INNER JOIN usuarios u ON u.id = pr.usuario_id
                 WHERE pr.grupo_id = :grupo
                 ORDER BY pr.id ASC'
            );
            $stmt->execute(['grupo' => $propio['grupo_id']]);
            return $stmt->fetchAll();
        }
        $detalle = self::obtenerDetallado($prestamoId);
        return $detalle ? [$detalle] : [];
    }

    /**
     * Misma idea que obtenerGrupoDetallado(), pero bloqueando las filas
     * (FOR UPDATE) para poder modificarlas de forma segura dentro de una
     * transaccion ya abierta. Recibe la fila ya leida de "prestamos" (no el
     * detalle con JOIN) para no repetir la primera consulta.
     *
     * @param array<string,mixed> $prestamo
     * @return array<int,array<string,mixed>>
     */
    private static function filasDelGrupoParaActualizar(PDO $pdo, array $prestamo): array
    {
        if (empty($prestamo['grupo_id'])) {
            return [$prestamo];
        }
        $stmt = $pdo->prepare('SELECT * FROM prestamos WHERE grupo_id = :grupo FOR UPDATE');
        $stmt->execute(['grupo' => $prestamo['grupo_id']]);
        return $stmt->fetchAll();
    }

    /**
     * Limpia y ordena el pedido "libro_id => cantidad" que llega del
     * formulario: descarta ids o cantidades inválidas, y ordena por libro_id
     * para que dos préstamos simultáneos siempre bloqueen los libros en el
     * mismo orden (evita que se traben entre sí/deadlock).
     *
     * @param array<int|string,int|string> $cantidadesPorLibro
     * @return array<int,int> libro_id => cantidad (ambos > 0)
     */
    private static function normalizarCantidades(array $cantidadesPorLibro): array
    {
        $limpio = [];
        foreach ($cantidadesPorLibro as $libroId => $cantidad) {
            $libroId = (int) $libroId;
            $cantidad = (int) $cantidad;
            if ($libroId > 0 && $cantidad > 0) {
                $limpio[$libroId] = $cantidad;
            }
        }
        ksort($limpio);
        return $limpio;
    }

    /** Cuántas unidades de cada libro hacen falta en este grupo (puede haber más de una fila con el mismo libro_id: pidieron varias copias). */
    private static function cantidadesDelGrupo(array $grupo): array
    {
        $cantidades = [];
        foreach ($grupo as $item) {
            $libroId = (int) $item['libro_id'];
            $cantidades[$libroId] = ($cantidades[$libroId] ?? 0) + 1;
        }
        return $cantidades;
    }

    /**
     * Da de alta un préstamo directo (lo hace un bibliotecario/administrador)
     * de uno o varios libros a la vez para el mismo lector, incluso varias
     * copias de un mismo título (por ejemplo, 3 ejemplares de "Harry
     * Potter"). Todos quedan atados por un mismo grupo_id: más adelante se
     * devuelven siempre juntos.
     *
     * @param array<int|string,int|string> $cantidadesPorLibro libro_id => cantidad
     * @return int[] los ids de los préstamos creados (uno por ejemplar)
     */
    public static function registrar(int $usuarioId, array $cantidadesPorLibro, ?int $usuarioRegistroId, ?string $observaciones): array
    {
        $cantidadesPorLibro = self::normalizarCantidades($cantidadesPorLibro);
        if (empty($cantidadesPorLibro)) {
            throw new RuntimeException('Elegí al menos un libro (con cantidad mayor a cero) para prestar.');
        }

        $fechaDevolucionEstimada = self::calcularFechaDevolucionEstimada();
        $pdo = obtenerConexion();
        $pdo->beginTransaction();
        try {
            // Primero bloqueamos y revisamos el stock de TODOS los libros pedidos
            // (con la cantidad completa que se necesita de cada uno) antes de
            // descontar nada: si falta uno solo, no se presta ninguno.
            $librosSinStock = [];
            foreach ($cantidadesPorLibro as $libroId => $cantidadNecesaria) {
                $stmt = $pdo->prepare('SELECT titulo, stock_disponible FROM libros WHERE id = :id FOR UPDATE');
                $stmt->execute(['id' => $libroId]);
                $libro = $stmt->fetch();
                if (!$libro) {
                    $librosSinStock[] = "ID $libroId";
                    continue;
                }
                if ((int) $libro['stock_disponible'] < $cantidadNecesaria) {
                    $librosSinStock[] = $libro['titulo'] . ' (pediste ' . $cantidadNecesaria . ', hay ' . (int) $libro['stock_disponible'] . ' disponibles)';
                }
            }
            if (!empty($librosSinStock)) {
                throw new RuntimeException('No hay suficientes ejemplares disponibles de: ' . implode(', ', $librosSinStock) . '.');
            }

            $idsCreados = [];
            foreach ($cantidadesPorLibro as $libroId => $cantidadNecesaria) {
                $pdo->prepare('UPDATE libros SET stock_disponible = stock_disponible - :cantidad WHERE id = :id')
                    ->execute(['cantidad' => $cantidadNecesaria, 'id' => $libroId]);

                for ($i = 0; $i < $cantidadNecesaria; $i++) {
                    $insert = $pdo->prepare(
                        'INSERT INTO prestamos (usuario_id, libro_id, usuario_registro_id, fecha_devolucion_estimada, observaciones, estado)
                         VALUES (:usuario_id, :libro_id, :usuario_registro_id, :fecha_dev, :observaciones, "prestado")'
                    );
                    $insert->execute([
                        'usuario_id'          => $usuarioId,
                        'libro_id'            => $libroId,
                        'usuario_registro_id' => $usuarioRegistroId,
                        'fecha_dev'           => $fechaDevolucionEstimada,
                        'observaciones'       => $observaciones,
                    ]);
                    $idsCreados[] = (int) $pdo->lastInsertId();
                }
            }

            // Atamos todas las filas de esta tanda con el id de la primera, asi
            // quedan marcadas como un unico "préstamo múltiple".
            $grupoId = $idsCreados[0];
            foreach ($idsCreados as $id) {
                $pdo->prepare('UPDATE prestamos SET grupo_id = :grupo WHERE id = :id')
                    ->execute(['grupo' => $grupoId, 'id' => $id]);
            }

            $pdo->commit();
            return $idsCreados;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Un lector solicita uno o varios libros a la vez (incluso varias copias
     * de un mismo título): queda(n) pendiente(s) hasta que un bibliotecario/
     * administrador los apruebe o rechace, siempre todos juntos (comparten
     * grupo_id igual que en registrar()).
     *
     * @param array<int|string,int|string> $cantidadesPorLibro libro_id => cantidad
     * @return int[] los ids de las solicitudes creadas (una por ejemplar)
     */
    public static function solicitar(int $usuarioId, array $cantidadesPorLibro, ?string $observaciones): array
    {
        $cantidadesPorLibro = self::normalizarCantidades($cantidadesPorLibro);
        if (empty($cantidadesPorLibro)) {
            throw new RuntimeException('Elegí al menos un libro (con cantidad mayor a cero) para solicitar.');
        }

        $fechaDevolucionEstimada = self::calcularFechaDevolucionEstimada();
        $pdo = obtenerConexion();
        $pdo->beginTransaction();
        try {
            $idsCreados = [];
            foreach ($cantidadesPorLibro as $libroId => $cantidadNecesaria) {
                for ($i = 0; $i < $cantidadNecesaria; $i++) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO prestamos (usuario_id, libro_id, usuario_registro_id, fecha_devolucion_estimada, observaciones, estado)
                         VALUES (:usuario_id, :libro_id, NULL, :fecha_dev, :observaciones, "pendiente")'
                    );
                    $stmt->execute([
                        'usuario_id'    => $usuarioId,
                        'libro_id'      => $libroId,
                        'fecha_dev'     => $fechaDevolucionEstimada,
                        'observaciones' => $observaciones,
                    ]);
                    $idsCreados[] = (int) $pdo->lastInsertId();
                }
            }

            $grupoId = $idsCreados[0];
            foreach ($idsCreados as $id) {
                $pdo->prepare('UPDATE prestamos SET grupo_id = :grupo WHERE id = :id')
                    ->execute(['grupo' => $grupoId, 'id' => $id]);
            }

            $pdo->commit();
            return $idsCreados;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Aprueba la solicitud y, si pidió varios libros (o varias copias de uno) juntos, aprueba todo el grupo a la vez. */
    public static function aprobar(int $prestamoId, int $usuarioRegistroId): void
    {
        $pdo = obtenerConexion();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM prestamos WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $prestamoId]);
            $prestamo = $stmt->fetch();

            if (!$prestamo || $prestamo['estado'] !== 'pendiente') {
                throw new RuntimeException('La solicitud no existe o ya fue resuelta.');
            }

            $grupo = self::filasDelGrupoParaActualizar($pdo, $prestamo);
            $necesarioPorLibro = self::cantidadesDelGrupo($grupo);

            // Revisamos el stock de CADA libro del grupo (sumando cuantas copias
            // hacen falta de ese mismo titulo) antes de aprobar nada: si falta
            // una sola unidad, no se aprueba nada (queda todo pendiente).
            $librosSinStock = [];
            foreach ($necesarioPorLibro as $libroId => $cantidadNecesaria) {
                $stmtLibro = $pdo->prepare('SELECT titulo, stock_disponible FROM libros WHERE id = :id FOR UPDATE');
                $stmtLibro->execute(['id' => $libroId]);
                $libro = $stmtLibro->fetch();
                if (!$libro) {
                    $librosSinStock[] = 'ID ' . $libroId;
                    continue;
                }
                if ((int) $libro['stock_disponible'] < $cantidadNecesaria) {
                    $librosSinStock[] = $libro['titulo'] . ' (hacen falta ' . $cantidadNecesaria . ', hay ' . (int) $libro['stock_disponible'] . ' disponibles)';
                }
            }
            if (!empty($librosSinStock)) {
                throw new RuntimeException('No se puede aprobar: no hay suficientes ejemplares disponibles de ' . implode(', ', $librosSinStock) . '.');
            }

            foreach ($necesarioPorLibro as $libroId => $cantidadNecesaria) {
                $pdo->prepare('UPDATE libros SET stock_disponible = stock_disponible - :cantidad WHERE id = :id')
                    ->execute(['cantidad' => $cantidadNecesaria, 'id' => $libroId]);
            }

            $fechaDevolucionEstimada = self::calcularFechaDevolucionEstimada();
            foreach ($grupo as $item) {
                $pdo->prepare('UPDATE prestamos SET estado = "prestado", usuario_registro_id = :registro, fecha_devolucion_estimada = :fecha_dev WHERE id = :id')
                    ->execute([
                        'registro'  => $usuarioRegistroId,
                        'fecha_dev' => $fechaDevolucionEstimada,
                        'id'        => $item['id'],
                    ]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Rechaza la solicitud y, si pidió varios libros juntos, rechaza todo el grupo a la vez. */
    public static function rechazar(int $prestamoId, int $usuarioRegistroId): void
    {
        $pdo = obtenerConexion();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM prestamos WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $prestamoId]);
            $prestamo = $stmt->fetch();

            if (!$prestamo || $prestamo['estado'] !== 'pendiente') {
                throw new RuntimeException('La solicitud no existe o ya fue resuelta.');
            }

            $grupo = self::filasDelGrupoParaActualizar($pdo, $prestamo);
            foreach ($grupo as $item) {
                $pdo->prepare('UPDATE prestamos SET estado = "rechazado", usuario_registro_id = :registro WHERE id = :id')
                    ->execute(['registro' => $usuarioRegistroId, 'id' => $item['id']]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Deshace una aprobación o rechazo; si era un grupo de varios libros, deshace todo el grupo a la vez. */
    public static function cancelar(int $prestamoId): void
    {
        $pdo = obtenerConexion();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM prestamos WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $prestamoId]);
            $prestamo = $stmt->fetch();

            if (!$prestamo || !in_array($prestamo['estado'], ['prestado', 'rechazado'], true)) {
                throw new RuntimeException('Este préstamo no se puede cancelar (ya está pendiente, fue devuelto, o venció).');
            }

            $grupo = self::filasDelGrupoParaActualizar($pdo, $prestamo);
            foreach ($grupo as $item) {
                if ($item['estado'] === 'prestado') {
                    // +1 por cada fila: si habia 3 copias del mismo libro en el grupo, esto corre 3 veces y repone las 3.
                    $pdo->prepare('UPDATE libros SET stock_disponible = stock_disponible + 1 WHERE id = :id')
                        ->execute(['id' => $item['libro_id']]);
                }
                $pdo->prepare('UPDATE prestamos SET estado = "pendiente", usuario_registro_id = NULL WHERE id = :id')
                    ->execute(['id' => $item['id']]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Registra la devolución. Si el préstamo incluía varios libros (o varias
     * copias de uno) a la vez, se devuelven TODOS juntos (no se puede
     * devolver uno solo del grupo) — pero el ESTADO de cada ejemplar se marca
     * individualmente: $estadoPorFila es [prestamo_id => "bueno"|"danado"].
     * Si una fila del grupo no viene en el array, se asume "bueno".
     *
     * Al cerrar la devolución, aplica la consecuencia en la calificación del
     * lector (resta 1 estrella por cada ejemplar dañado, y 1 más si la
     * devolución llegó tarde; o suma a su racha de buena conducta si todo
     * volvió bien), todo dentro de la misma transacción.
     */
    public static function registrarDevolucion(int $prestamoId, array $estadoPorFila = []): void
    {
        $pdo = obtenerConexion();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM prestamos WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $prestamoId]);
            $prestamo = $stmt->fetch();

            if (!$prestamo || !in_array($prestamo['estado'], ['prestado', 'vencido'], true)) {
                throw new RuntimeException('El préstamo no existe, no fue aprobado todavía, o ya fue devuelto.');
            }

            // Días de atraso: comparamos la fecha de hoy con la estimada (son
            // iguales para todas las filas del grupo, se fijaron juntas).
            $diasAtraso = max(0, (int) floor((strtotime(date('Y-m-d')) - strtotime($prestamo['fecha_devolucion_estimada'])) / 86400));

            $grupo = self::filasDelGrupoParaActualizar($pdo, $prestamo);

            // Títulos de cada fila del grupo, para poder decir en el historial
            // de calificación CUÁL libro volvió dañado (no todos tienen por qué serlo).
            $idsEnGrupo = array_column($grupo, 'id');
            $titulosPorId = [];
            if (!empty($idsEnGrupo)) {
                $marcas = implode(',', array_fill(0, count($idsEnGrupo), '?'));
                $stmtTitulos = $pdo->prepare("SELECT pr.id, l.titulo FROM prestamos pr INNER JOIN libros l ON l.id = pr.libro_id WHERE pr.id IN ($marcas)");
                $stmtTitulos->execute($idsEnGrupo);
                foreach ($stmtTitulos->fetchAll() as $fila) {
                    $titulosPorId[(int) $fila['id']] = $fila['titulo'];
                }
            }

            $librosDanados = [];
            foreach ($grupo as $item) {
                if (!in_array($item['estado'], ['prestado', 'vencido'], true)) {
                    continue; // ya estaba devuelto u otro estado raro: no lo tocamos.
                }
                $estadoFila = ($estadoPorFila[(int) $item['id']] ?? 'bueno') === 'danado' ? 'danado' : 'bueno';
                $pdo->prepare('UPDATE prestamos SET estado = "devuelto", fecha_devolucion_real = NOW(), estado_devolucion = :estado_devolucion WHERE id = :id')
                    ->execute(['estado_devolucion' => $estadoFila, 'id' => $item['id']]);
                $pdo->prepare('UPDATE libros SET stock_disponible = stock_disponible + 1 WHERE id = :id')
                    ->execute(['id' => $item['libro_id']]);
                if ($estadoFila === 'danado') {
                    $librosDanados[] = $titulosPorId[(int) $item['id']] ?? ('libro #' . $item['libro_id']);
                }
            }

            Calificacion::aplicarConsecuenciaDevolucion(
                $pdo,
                (int) $prestamo['usuario_id'],
                $prestamoId,
                $diasAtraso,
                $librosDanados
            );

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Cuántos libros tiene un lector hoy "comprometidos" (pendientes, prestados o vencidos): cuenta para su tope según calificación. */
    public static function cantidadComprometidaDeUsuario(int $usuarioId): int
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM prestamos WHERE usuario_id = :usuario_id AND estado IN ('pendiente', 'prestado', 'vencido')"
        );
        $stmt->execute(['usuario_id' => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    public static function actualizarVencidos(): void
    {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare(
            'UPDATE prestamos SET estado = "vencido" WHERE estado = "prestado" AND fecha_devolucion_estimada < CURDATE()'
        );
        $stmt->execute();
    }
}
