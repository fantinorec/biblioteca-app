<?php

/**
 * Cliente para la API de Gemini (Google AI), adaptado del ejemplo que dio el profesor.
 * Se usa acá para pedirle a la IA que recomiende libros del catálogo de la biblioteca.
 *
 * Modelos disponibles: https://ai.google.dev/gemini-api/docs/models?hl=es-419
 * El ejemplo original del profesor usaba gemini-1.5-flash-8b, pero Google ya lo dio de baja
 * (la API devuelve error 404). Por defecto usamos gemini-3.5-flash-lite: es, al momento de
 * escribir esto, el modelo estable más rápido y económico disponible. Si en el futuro la API
 * vuelve a rechazar el modelo por nombre, hay que revisar la lista de arriba y actualizar este valor.
 */
class GeminiApiClient
{
    private string $apiKey;
    private string $modelo;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct(string $apiKey, string $modelo = 'gemini-3.5-flash-lite')
    {
        $this->apiKey = $apiKey;
        $this->modelo = $modelo;
    }

    /**
     * Le pide contenido a Gemini y devuelve la respuesta en texto plano.
     *
     * @param string $entradaUsuario    El pedido/pregunta del usuario.
     * @param string $instruccionSistema El "rol" fijo que debe mantener la IA (no lo decide el usuario,
     *                                   así evitamos que alguien le cambie el comportamiento desde el formulario).
     * @param int    $maxTokens         Límite de tokens de la respuesta, para controlar costo y tiempo.
     */
    public function generarContenido(string $entradaUsuario, string $instruccionSistema, int $maxTokens = 500): string
    {
        $url = $this->baseUrl . $this->modelo . ':generateContent';

        $payload = [
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [
                        ['text' => $entradaUsuario],
                    ],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens'  => $maxTokens,
                'responseMimeType' => 'text/plain',
            ],
            'systemInstruction' => [
                'parts' => [
                    ['text' => $instruccionSistema],
                ],
            ],
        ];

        $ch = curl_init();
        $opciones = [
            CURLOPT_URL            => $url . '?key=' . $this->apiKey,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ];

        // En Windows/XAMPP, PHP no suele traer configurado un almacén de certificados CA
        // (curl.cainfo vacío en el php.ini), y sin esto cURL falla siempre con "SSL certificate
        // problem: unable to get local issuer certificate", incluso contra servidores válidos
        // como este de Google. Le pasamos el mismo bundle de CAs que ya usa el mailer (SmtpMailer.php).
        $cafile = __DIR__ . '/cacert.pem';
        if (is_readable($cafile)) {
            $opciones[CURLOPT_CAINFO] = $cafile;
        }

        curl_setopt_array($ch, $opciones);

        $respuesta = curl_exec($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Error de conexión con Gemini: ' . $error);
        }

        if ($codigoHttp !== 200) {
            throw new Exception('Gemini respondió con un error (HTTP ' . $codigoHttp . '): ' . $respuesta);
        }

        return $this->parsearRespuesta($respuesta);
    }

    private function parsearRespuesta(string $respuesta): string
    {
        $datos = json_decode($respuesta, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('No se pudo interpretar la respuesta de Gemini.');
        }

        if (isset($datos['error'])) {
            throw new Exception('Error de la API de Gemini: ' . $datos['error']['message']);
        }

        if (isset($datos['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($datos['candidates'][0]['content']['parts'][0]['text']);
        }

        throw new Exception('Gemini no devolvió una respuesta con el formato esperado.');
    }
}
