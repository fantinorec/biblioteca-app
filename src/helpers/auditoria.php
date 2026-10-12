<?php

function registrar_auditoria(?int $usuarioId, ?string $usuarioEmail, string $accion, ?string $tabla = null, ?int $registroId = null, ?string $detalle = null): void
{
    try {
        $pdo = obtenerConexion();
        $stmt = $pdo->prepare(
            'INSERT INTO auditoria (usuario_id, usuario_email, accion, tabla_afectada, registro_id, detalle, ip)
             VALUES (:usuario_id, :usuario_email, :accion, :tabla, :registro_id, :detalle, :ip)'
        );
        $stmt->execute([
            'usuario_id'    => $usuarioId,
            'usuario_email' => $usuarioEmail,
            'accion'        => $accion,
            'tabla'         => $tabla,
            'registro_id'   => $registroId,
            'detalle'       => $detalle,
            'ip'            => ip_cliente(),
        ]);
    } catch (Throwable $e) {
        // Si falla el registro de auditoría, no debe frenar lo que estaba haciendo el usuario.
        error_log('No se pudo registrar auditoria: ' . $e->getMessage());
    }
}
