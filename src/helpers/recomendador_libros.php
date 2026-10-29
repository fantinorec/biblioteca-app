<?php

/**
 * Arma el prompt con el catálogo real de la biblioteca y le pide a Gemini que recomiende
 * libros SOLO de esa lista (para que no invente libros que la biblioteca no tiene).
 */
function generar_recomendacion_libros(string $pedidoLector): string
{
    $apiKey = env('GEMINI_API_KEY', '');
    if ($apiKey === '') {
        throw new Exception('Falta configurar GEMINI_API_KEY en el archivo .env.');
    }

    $libros = Libro::listar('activo');
    if (empty($libros)) {
        return 'Todavía no hay libros cargados en el catálogo como para recomendarte algo.';
    }

    $listaCatalogo = '';
    foreach ($libros as $l) {
        $disponibilidad = ((int) $l['stock_disponible'] > 0) ? 'disponible' : 'sin stock por ahora';
        $listaCatalogo .= sprintf(
            "- \"%s\" de %s %s. Categoría: %s. %s%s.\n",
            $l['titulo'],
            $l['autor_nombre'],
            $l['autor_apellido'],
            $l['categoria_nombre'],
            $l['anio_publicacion'] ? 'Año: ' . (int) $l['anio_publicacion'] . '. ' : '',
            ucfirst($disponibilidad)
        );
    }

    $instruccionSistema = <<<TXT
Sos el asistente de recomendaciones de una biblioteca. Tu única fuente de libros es el catálogo
que te paso a continuación: NUNCA recomiendes un libro que no esté en esa lista, aunque lo conozcas
de otro lado. Si nada del catálogo se ajusta bien a lo que pide el usuario, decilo con honestidad y
sugerí lo más parecido que haya, aclarando que no es una coincidencia perfecta.

Respondé siempre en español, en un tono cercano y amable, como alguien que te ayuda a elegir qué leer.
Recomendá como máximo 3 libros. Para cada uno, mencioná el título, el autor y una razón breve (1-2
frases) de por qué podría gustarle según lo que pidió. Si un libro está "sin stock por ahora",
mencionalo igual pero aclará esa condición. No repitas la pregunta del usuario, andá directo a la
recomendación. No uses formato Markdown (nada de **negritas** ni listas con guiones), escribí en
párrafos simples de texto plano.

Catálogo disponible:
$listaCatalogo
TXT;

    $cliente = new GeminiApiClient($apiKey);
    return $cliente->generarContenido($pedidoLector, $instruccionSistema);
}
