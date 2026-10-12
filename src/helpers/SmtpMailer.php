<?php

class SmtpMailer
{
    private string $host;
    private int $port;
    private string $secure;
    private string $usuario;
    private string $password;
    private string $nombreRemitente;

    private $socket = null;
    private string $ultimoError = '';

    public function __construct()
    {
        $this->host            = env('SMTP_HOST', '');
        $this->port             = (int) env('SMTP_PORT', 587);
        $this->secure            = strtolower(env('SMTP_SECURE', 'tls'));
        $this->usuario           = env('SMTP_USER', '');
        $this->password          = env('SMTP_PASS', '');
        $this->nombreRemitente   = env('SMTP_FROM_NAME', env('APP_NAME', 'Biblioteca'));
    }

    public function getUltimoError(): string
    {
        return $this->ultimoError;
    }

    public function enviar(string $destinatario, string $asunto, string $cuerpoHtml): bool
    {
        if ($this->host === '' || $this->usuario === '') {
            $this->ultimoError = 'La configuracion SMTP (.env) esta incompleta.';
            error_log('[SMTP] ' . $this->ultimoError);
            return false;
        }

        foreach ([true, false] as $verificarCertificado) {
            try {
                $this->conectar($verificarCertificado);
                $this->handshake();
                if ($this->secure === 'tls') {
                    $this->comando("STARTTLS", 220);

                    error_clear_last();
                    if (!@stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                        $detalle = error_get_last()['message'] ?? 'motivo desconocido';
                        throw new RuntimeException("No se pudo iniciar TLS con el servidor SMTP ({$detalle}).");
                    }

                    $this->handshake();
                }
                $this->autenticar();
                $this->enviarMensaje($destinatario, $asunto, $cuerpoHtml);
                $this->comando("QUIT", 221, true);
                fclose($this->socket);
                $this->socket = null;
                return true;
            } catch (Throwable $e) {
                if (is_resource($this->socket)) {
                    fclose($this->socket);
                    $this->socket = null;
                }
                $esErrorDeCertificado = stripos($e->getMessage(), 'certificat') !== false;
                if ($verificarCertificado && $esErrorDeCertificado) {
                    error_log('[SMTP] Verificacion de certificado fallo (posible antivirus interceptando la conexion), reintentando sin verificar: ' . $e->getMessage());
                    continue;
                }
                $this->ultimoError = $e->getMessage();
                error_log('[SMTP] Error enviando correo: ' . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    private function conectar(bool $verificarCertificado): void
    {
        $prefijo = ($this->secure === 'ssl') ? 'ssl://' : '';

        $opcionesSsl = [
            'peer_name'   => $this->host,
            'SNI_enabled' => true,
        ];
        if ($verificarCertificado) {
            $opcionesSsl['verify_peer'] = true;
            $opcionesSsl['verify_peer_name'] = true;
            $cafile = __DIR__ . '/cacert.pem';
            if (is_readable($cafile)) {
                $opcionesSsl['cafile'] = $cafile;
            }
        } else {
            $opcionesSsl['verify_peer'] = false;
            $opcionesSsl['verify_peer_name'] = false;
            $opcionesSsl['allow_self_signed'] = true;
        }
        $contexto = stream_context_create(['ssl' => $opcionesSsl]);

        $this->socket = @stream_socket_client(
            "{$prefijo}{$this->host}:{$this->port}",
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT,
            $contexto
        );
        if (!$this->socket) {
            throw new RuntimeException("No se pudo conectar a {$this->host}:{$this->port} ($errstr)");
        }
        $this->leerRespuesta();
    }

    private function handshake(): void
    {
        $host = gethostname() ?: 'localhost';
        $this->comando("EHLO {$host}", 250);
    }

    private function autenticar(): void
    {
        $this->comando('AUTH LOGIN', 334);
        $this->comando(base64_encode($this->usuario), 334);
        $this->comando(base64_encode($this->password), 235);
    }

    private function enviarMensaje(string $destinatario, string $asunto, string $cuerpoHtml): void
    {
        $this->comando("MAIL FROM:<{$this->usuario}>", 250);
        $this->comando("RCPT TO:<{$destinatario}>", 250);
        $this->comando('DATA', 354);

        $asuntoCodificado = '=?UTF-8?B?' . base64_encode($asunto) . '?=';
        $remitenteCodificado = '=?UTF-8?B?' . base64_encode($this->nombreRemitente) . '?=';

        $headers = [];
        $headers[] = "From: {$remitenteCodificado} <{$this->usuario}>";
        $headers[] = "To: <{$destinatario}>";
        $headers[] = "Subject: {$asuntoCodificado}";
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $headers[] = 'Date: ' . date('r');

        $cuerpoEscapado = preg_replace('/^\./m', '..', $cuerpoHtml);

        $mensaje = implode("\r\n", $headers) . "\r\n\r\n" . $cuerpoEscapado . "\r\n.";
        $this->comando($mensaje, 250);
    }

    private function comando(string $comando, int $codigoEsperado, bool $ignorarError = false): string
    {
        fwrite($this->socket, $comando . "\r\n");
        $respuesta = $this->leerRespuesta();
        $codigo = (int) substr($respuesta, 0, 3);
        if (!$ignorarError && $codigo !== $codigoEsperado) {
            throw new RuntimeException("El servidor SMTP respondio inesperadamente: {$respuesta}");
        }
        return $respuesta;
    }

    private function leerRespuesta(): string
    {
        $respuesta = '';
        while (($linea = fgets($this->socket, 515)) !== false) {
            $respuesta .= $linea;

            if (isset($linea[3]) && $linea[3] === ' ') {
                break;
            }
        }
        return $respuesta;
    }
}
