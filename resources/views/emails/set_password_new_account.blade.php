<!DOCTYPE html>
<html lang="es">
<head>
    <title>Restablecer contraseña</title>
</head>
<body>
    <h1>Hola,</h1>
    <p>Has recibido este correo electrónico se te ha dado de alta en la plataforma Portal Objetos de Aprendizaje.</p>
    <p>Haz clic en el botón de abajo para establecer tu contraseña:</p>
    <a href="{{ $parameters['url'] }}" style="background-color: {{app('general_options')['color_1']}}; color: white; padding: 10px 20px; text-decoration: none; margin-top: 20px; margin-bottom: 20px;">Restablecer contraseña</a>
    <p>Gracias,</p>

    @if ($general_options['company_name'] != '')
        <p>El equipo de {{ $general_options['company_name'] }}</p>
    @endif
</body>
</html>
