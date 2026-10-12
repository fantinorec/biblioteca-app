<?php
require_once __DIR__ . '/SmtpMailer.php';

function plantilla_email(string $titulo, string $cuerpo, string $textoBoton, string $urlBoton): string
{
    $nombreApp = h(env('APP_NAME', 'Biblioteca'));
    return <<<HTML
    <div style="font-family: Arial, Helvetica, sans-serif; background:#f4efe6; padding:24px;">
      <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #ded2ba;">
        <div style="background:#6b6f3a;padding:20px 24px;">
          <h1 style="color:#f4efe6;margin:0;font-size:20px;">{$nombreApp}</h1>
        </div>
        <div style="padding:24px;color:#3a352a;">
          <h2 style="margin-top:0;color:#4b4d28;">{$titulo}</h2>
          <p style="line-height:1.5;">{$cuerpo}</p>
          <p style="text-align:center;margin:32px 0;">
            <a href="{$urlBoton}" style="background:#6b6f3a;color:#f4efe6;text-decoration:none;padding:12px 28px;border-radius:6px;display:inline-block;font-weight:bold;">{$textoBoton}</a>
          </p>
          <p style="font-size:12px;color:#8a8266;">Si el boton no funciona, copia y pega este enlace en tu navegador:<br>{$urlBoton}</p>
        </div>
      </div>
    </div>
    HTML;
}

function enviar_email_verificacion(string $destinatario, string $nombre, string $token): bool
{
    $url = rtrim(env('APP_URL', ''), '/') . '/verificar_email.php?token=' . urlencode($token);
    $cuerpo = plantilla_email(
        'Confirma tu cuenta',
        'Hola ' . h($nombre) . ', gracias por registrarte. Para activar tu cuenta necesitamos que confirmes tu dirección de correo haciendo clic en el siguiente botón. El enlace vence en 24 horas.',
        'Confirmar mi cuenta',
        $url
    );
    $mailer = new SmtpMailer();
    return $mailer->enviar($destinatario, 'Confirma tu cuenta - ' . env('APP_NAME', 'Biblioteca'), $cuerpo);
}

function enviar_email_recuperacion(string $destinatario, string $nombre, string $token): bool
{
    $url = rtrim(env('APP_URL', ''), '/') . '/resetear_password.php?token=' . urlencode($token);
    $cuerpo = plantilla_email(
        'Recuperación de contraseña',
        'Hola ' . h($nombre) . ', recibimos una solicitud para restablecer tu contraseña. Si no fuiste vos, podés ignorar este correo. El enlace vence en 1 hora.',
        'Restablecer contraseña',
        $url
    );
    $mailer = new SmtpMailer();
    return $mailer->enviar($destinatario, 'Recuperación de contraseña - ' . env('APP_NAME', 'Biblioteca'), $cuerpo);
}
