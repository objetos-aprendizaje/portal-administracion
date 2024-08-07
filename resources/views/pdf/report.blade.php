<!DOCTYPE html>
<html>
<head>
    <title>Informe de Pruebas</title>
    <style>
        /* Estilos CSS para el informe */
    </style>
</head>
<body>
    <h1>Informe de Pruebas</h1>
    <table>
        <thead>
            <tr>
                <th>Prueba</th>
                <th>Resultado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($testResults as $test => $result)
                <tr>
                    <td>{{ $test }}</td>
                    <td>{{ $result }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>