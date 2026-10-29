<?php

function icono(string $clave, string $clase = ''): string
{
    $trazos = [
        'home'     => '<path d="M3.5 11.5 12 4l8.5 7.5"/><path d="M5.5 10v9.5a1 1 0 0 0 1 1H10v-6h4v6h3.5a1 1 0 0 0 1-1V10"/>',
        'users'    => '<circle cx="9" cy="8" r="3.3"/><path d="M2.8 20c0-3.6 2.8-6 6.2-6s6.2 2.4 6.2 6"/><path d="M15.8 8.3a3.1 3.1 0 1 1 3.1 3.6"/><path d="M16.3 14.3c2.4.6 3.9 2.5 3.9 5.7"/>',
        'shield'   => '<path d="M12 3.2 19 6v5.3c0 5-3.1 8.4-7 9.5-3.9-1.1-7-4.5-7-9.5V6l7-2.8Z"/><path d="M9 12l2 2 4-4.2"/>',
        'lock'     => '<rect x="5" y="10.5" width="14" height="9.3" rx="2"/><path d="M8 10.5V7.3a4 4 0 0 1 8 0v3.2"/>',
        'book'     => '<path d="M12 6c-1.9-1.3-4.5-1.8-7.2-1.6v13.4c2.7-.2 5.3.3 7.2 1.6 1.9-1.3 4.5-1.8 7.2-1.6V4.4C16.5 4.2 13.9 4.7 12 6Z"/><path d="M12 6v13.4"/>',
        'pen'      => '<path d="M4.5 19.5 5.4 15.6 15.8 5.2a1.6 1.6 0 0 1 2.3 0l1 1a1.6 1.6 0 0 1 0 2.3L8.7 18.6 4.5 19.5Z"/><path d="M14.3 7.1l2.9 2.9"/>',
        'tag'      => '<path d="M12.9 3.6 20 4.2l.6 7.1L11.7 20.2a1.9 1.9 0 0 1-2.7 0l-6-6a1.9 1.9 0 0 1 0-2.7L12.9 3.6Z"/><circle cx="15.6" cy="8.2" r="1.3"/>',
        'building' => '<rect x="4" y="3" width="9.5" height="18"/><rect x="13.5" y="9.3" width="6.5" height="11.7"/><path d="M7 7h3M7 11h3M7 15h3"/>',
        'exchange' => '<path d="M4 8h14.5M15.3 4.2 19 8l-3.7 3.8"/><path d="M20 16H5.5M8.7 12.2 5 16l3.7 3.8"/>',
        'chart'    => '<path d="M4 20V4"/><path d="M4 20h16"/><rect x="7" y="12.5" width="3" height="7.5"/><rect x="12.2" y="8" width="3" height="12"/><rect x="17.4" y="4.5" width="3" height="15.5"/>',
        'history'  => '<circle cx="12" cy="12.5" r="8.3"/><path d="M12 8v4.5l3.3 2"/><path d="M6.2 4.8 5.3 8.4l3.6-.7"/>',
        'download' => '<path d="M12 3.5v11.7"/><path d="M7 10.7l5 5 5-5"/><path d="M5 20h14"/>',
        'logo'     => '<path d="M12 6c-1.9-1.3-4.5-1.8-7.2-1.6v13.4c2.7-.2 5.3.3 7.2 1.6 1.9-1.3 4.5-1.8 7.2-1.6V4.4C16.5 4.2 13.9 4.7 12 6Z"/><path d="M12 6v13.4"/>',
        'star'     => '<path d="M12 3.3l2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9Z"/>',
    ];

    $contenido = $trazos[$clave] ?? '<circle cx="12" cy="12" r="8"/>';
    $claseAtributo = $clase !== '' ? ' ' . $clase : '';

    return '<svg class="icono' . $claseAtributo . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $contenido . '</svg>';
}

function icono_js(string $clave, string $clase = ''): string
{
    return json_encode(icono($clave, $clase), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
