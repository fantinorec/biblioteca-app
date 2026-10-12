<?php
require __DIR__ . '/../src/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "APP_URL=" . env('APP_URL', '') . "\n";
echo "SMTP_HOST=" . env('SMTP_HOST', '') . "\n";
echo "SMTP_PORT=" . env('SMTP_PORT', '') . "\n";
echo "SMTP_SECURE=" . env('SMTP_SECURE', '') . "\n";
echo "SMTP_USER=" . env('SMTP_USER', '') . "\n";
echo "SMTP_PASS length=" . strlen((string) env('SMTP_PASS', '')) . "\n";

$cafile = __DIR__ . '/../src/helpers/cacert.pem';
echo "cacert.pem existe=" . (file_exists($cafile) ? 'si' : 'NO') . "\n";
echo "cacert.pem legible=" . (is_readable($cafile) ? 'si' : 'NO') . "\n";
echo "cacert.pem tamano=" . (file_exists($cafile) ? filesize($cafile) : 0) . "\n";
echo "PHP version=" . PHP_VERSION . "\n";
echo "OpenSSL version=" . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'desconocida') . "\n";
echo "---\n";

function intentarTls(string $host, int $port, array $opcionesSsl, string $etiqueta): void
{
    echo "--- Intento: {$etiqueta} ---\n";
    $contexto = stream_context_create(['ssl' => $opcionesSsl]);
    $socket = @stream_socket_client("{$host}:{$port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $contexto);
    if (!$socket) {
        echo "No conecto: $errstr\n";
        return;
    }
    fgets($socket, 515);
    fwrite($socket, "EHLO test\r\n");
    while (($l = fgets($socket, 515)) !== false) { if (isset($l[3]) && $l[3] === ' ') break; }
    fwrite($socket, "STARTTLS\r\n");
    while (($l = fgets($socket, 515)) !== false) { if (isset($l[3]) && $l[3] === ' ') break; }
    error_clear_last();
    $ok = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    if ($ok) {
        echo "TLS OK\n";
        $meta = stream_context_get_options($socket);
        $certResource = stream_context_get_params($socket)['options']['ssl']['peer_certificate'] ?? null;
        if ($certResource) {
            $info = openssl_x509_parse($certResource);
            echo "Certificado emitido para: " . ($info['subject']['CN'] ?? '?') . "\n";
            echo "Emisor: " . ($info['issuer']['CN'] ?? '?') . " / " . ($info['issuer']['O'] ?? '?') . "\n";
        }
    } else {
        $err = error_get_last()['message'] ?? 'sin detalle';
        echo "TLS FALLO: $err\n";
    }
    fclose($socket);
}

$cafileOk = is_readable($cafile);
intentarTls(env('SMTP_HOST', ''), (int) env('SMTP_PORT', 587), [
    'verify_peer' => true,
    'verify_peer_name' => true,
    'peer_name' => env('SMTP_HOST', ''),
    'SNI_enabled' => true,
    'capture_peer_cert' => true,
] + ($cafileOk ? ['cafile' => $cafile] : []), 'con verificacion (cacert.pem)');

intentarTls(env('SMTP_HOST', ''), (int) env('SMTP_PORT', 587), [
    'verify_peer' => false,
    'verify_peer_name' => false,
    'SNI_enabled' => true,
    'capture_peer_cert' => true,
    'allow_self_signed' => true,
], 'SIN verificacion (solo diagnostico)');

echo "\n=== Prueba real con SmtpMailer (como la usa la app) ===\n";
$mailer = new SmtpMailer();
$ok = $mailer->enviar((string) env('SMTP_USER', ''), 'Prueba de diagnostico', '<p>Prueba</p>');
echo $ok ? "OK: correo enviado\n" : "FALLO: " . $mailer->getUltimoError() . "\n";
