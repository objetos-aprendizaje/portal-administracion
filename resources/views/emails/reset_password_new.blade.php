<!DOCTYPE html>
<html>
<head>
    <title>Restablecer contraseña</title>
</head>
<body>
    <h1>Hola,</h1>
    <p>Has recibido este correo electrónico porque hemos recibido una solicitud de restablecimiento de contraseña para tu cuenta.</p>
    <p>Haz clic en el botón de abajo para restablecer tu contraseña:</p>
    <a href="{{ $parameters['url'] }}" style="background-color: {{app('general_options')['color_1']}}; color: white; padding: 10px 20px; text-decoration: none;">Restablecer contraseña</a>
    <p>Si no solicitaste un restablecimiento de contraseña, no se requiere ninguna acción adicional.</p>
    <p>Gracias,</p>

    @if ($general_options['commercial_name'] != '')
        <p>El equipo de {{ $general_options['commercial_name'] }}</p>
    @endif
</body>
</html>
