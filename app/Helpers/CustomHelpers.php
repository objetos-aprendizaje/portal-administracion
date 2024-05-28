<?php

use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

function validateHexadecimalColor($color)
{
    // Expresión regular para validar un color hexadecimal (#RRGGBB o #RGB)
    $regex = '/^#([A-Fa-f0-9]{3}){1,2}([A-Fa-f0-9]{2})?$/';

    return preg_match($regex, $color) === 1;
}

function generate_uuid()
{
    return (string) Str::uuid();
}

function formatDateTime($datetime)
{
    // Convertir la fecha y hora a un objeto DateTime
    $date = new DateTime($datetime);

    // Formatear la fecha y la hora
    return $date->format('d/m/Y \a \l\a\s H:i');
}

/**
 * Busca un elemento en un array multidimensional
 */
function findOneInArray($array, $key, $value)
{

    foreach ($array as $element) {
        if ($element[$key] === $value) {
            return $element;
        }
    }

    return null;
}

/**
 * Busca un elemento en un array de objetos
 */
function findOneInArrayOfObjects($arr, $key, $value)
{
    foreach ($arr as $prop) {
        if ($prop->{$key} === $value) {
            return $prop;
        }
    }

    return null;
}
function sanitizeFilename($filename)
{
    // Eliminar espacios en blanco
    $filename = str_replace(' ', '', $filename);

    // Eliminar puntos, paréntesis y otros caracteres especiales
    $filename = preg_replace('/[^A-Za-z0-9\-]/', '', $filename);

    return $filename;
}

/**
 * Recibe un fichero, ruta y nombre (opcional) y lo guarda en la ruta especificada.
 * Devuelve la ruta completa del fichero guardado.
 */
function saveFile($file, $destinationPath, $filename = null, $public_path = false)
{
    // Si el nombre del archivo no se proporciona, generarlo
    $extension = $file->getClientOriginalExtension();

    if (!$filename) {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $originalName = sanitizeFilename($originalName);
        $timestamp = time();
        $filename = "{$originalName}-{$timestamp}.{$extension}";
    } else {
        $filename = "{$filename}.{$extension}";
    }

    $internalDestinationPath = $destinationPath;
    // Determinar la ruta de destino
    if ($public_path) {
        public_path($internalDestinationPath);
    } else {
        $internalDestinationPath = storage_path($destinationPath);
    }

    // Comprobar si el directorio existe; si no, crearlo
    if (!is_dir($internalDestinationPath)) {
        mkdir($internalDestinationPath, 0777, true);
    }

    // Mover el archivo al directorio destino
    $file->move($internalDestinationPath, $filename);

    // Devolver la ruta completa del archivo
    return "{$destinationPath}/{$filename}";
}

/**
 * Elimina un archivo del sistema de archivos.
 */
function deleteFile($path)
{
    if (file_exists($path))
        unlink($path);
}


/**
 * Comprueba si el archivo tiene la extensión especificada.
 *
 * @param \Illuminate\Http\UploadedFile $file El archivo subido.
 * @param string $extension La extensión deseada sin el punto inicial.
 * @return bool Verdadero si el archivo tiene la extensión deseada, falso en caso contrario.
 */
function checkFileExtension($file, $extension)
{
    // Verifica si el archivo es realmente un archivo subido y tiene una extensión.
    if ($file && $file->getClientOriginalExtension()) {
        // Compara la extensión del archivo con la extensión deseada.
        // Utiliza strtolower para asegurar que la comparación no sea sensible a mayúsculas.
        return strtolower($file->getClientOriginalExtension()) === strtolower($extension);
    }

    // Retorna falso si no es un archivo subido o no tiene extensión.
    return false;
}


function formatDateTimeNotifications($date)
{
    $pastDate = new Carbon($date);
    $currentDate = Carbon::now();
    $interval = $currentDate->diff($pastDate);

    // Si la fecha es hoy
    if ($pastDate->isToday()) {
        if ($interval->h > 0) {
            return "Hace " . $interval->h . ($interval->h == 1 ? " hora" : " horas");
        } else {
            return "Hace " . $interval->i . ($interval->i == 1 ? " minuto" : " minutos");
        }
    }

    // Si la fecha fue durante la semana pasada
    $startOfCurrentWeek = $currentDate->copy()->startOfWeek();
    $startOfLastWeek = $currentDate->copy()->subWeek()->startOfWeek();

    if ($pastDate->greaterThanOrEqualTo($startOfLastWeek) && $pastDate->lessThan($startOfCurrentWeek)) {
        return "El " . $pastDate->isoFormat('dddd') . ", a las " . $pastDate->format('H:i');
    }

    // Si la fecha es más antigua que la semana pasada
    return $pastDate->format('d/m/Y') . " a las " . $pastDate->format('H:i');
}



function renderCategories($categories, $level = 1)
{
    $html = '';
    $icon_edit = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 15 16">
    <g clip-path="url(#clip0_224_4598)">
      <path d="M10.7812 15.4988H1.71879C0.770645 15.4988 0 14.7282 0 13.78V4.71757C0 3.76942 0.770645 2.99878 1.71879 2.99878H7.03125C7.29 2.99878 7.5 3.20878 7.5 3.46753C7.5 3.72628 7.29 3.93628 7.03125 3.93628H1.71879C1.28815 3.93628 0.9375 4.28693 0.9375 4.71757V13.78C0.9375 14.2107 1.28815 14.5613 1.71879 14.5613H10.7812C11.2119 14.5613 11.5625 14.2107 11.5625 13.78V8.46757C11.5625 8.20882 11.7725 7.99882 12.0313 7.99882C12.29 7.99882 12.5 8.20824 12.5 8.46757V13.78C12.5 14.7282 11.7294 15.4988 10.7812 15.4988Z" fill="#CCCCCC"/>
      <path d="M5.48218 10.485C5.35904 10.485 5.23899 10.4362 5.15087 10.3476C5.03963 10.2369 4.99214 10.0775 5.02281 9.92435L5.46467 7.71439C5.48275 7.62318 5.52772 7.53998 5.59273 7.47497L12.066 1.00254C12.7359 0.332487 13.8259 0.332487 14.4966 1.00254C14.8209 1.32686 14.9997 1.75819 14.9997 2.21756C14.9997 2.67692 14.8209 3.10814 14.4959 3.43258L8.02345 9.9057C7.95845 9.97127 7.87468 10.0157 7.78404 10.0338L5.57464 10.4756C5.54397 10.4819 5.51273 10.485 5.48218 10.485ZM6.35593 8.03757L6.08024 9.41875L7.46086 9.14249L13.8334 2.77008C13.9809 2.62188 14.0622 2.4263 14.0622 2.21756C14.0622 2.00882 13.9809 1.81312 13.8334 1.66504C13.5297 1.36062 13.034 1.36062 12.7285 1.66504L6.35593 8.03757Z"/>
      <path d="M13.281 4.45436C13.161 4.45436 13.041 4.4087 12.9498 4.3168L11.1823 2.54869C10.9991 2.36559 10.9991 2.06873 11.1823 1.88562C11.3654 1.70251 11.6622 1.70251 11.8454 1.88562L13.6129 3.65373C13.796 3.83684 13.796 4.1337 13.6129 4.3168C13.521 4.40813 13.401 4.45436 13.281 4.45436Z" fill="#CCCCCC"/>
    </g>
    <defs>
      <clipPath id="clip0_224_4598">
        <rect width="15" height="15" fill="white" transform="translate(0 0.5)"/>
      </clipPath>
    </defs>
  </svg>';

    // Primero, categorías sin hijos
    foreach ($categories as $category) {
        if (empty($category['subcategories'])) {
            $html .= "<div class='anidation-div' style='margin-left:{$level}em;'>";
            $html .= "<div class='flex'>";
            $html .= "<input type='checkbox' class='element-checkbox' id='{$category['uid']}'> ";
            $html .= "<label for='{$category['uid']}' class='element-label'>{$category['name']} </label> <button class='edit-btn' data-uid='{$category['uid']}'>{$icon_edit}</button>";
            $html .= "</div>";
            if ($category['description']) {
                $html .= " <p>{$category['description']}</p>";
            }
            $html .= '</div>';
        }
    }

    // Luego, categorías con hijos
    foreach ($categories as $category) {
        if (!empty($category['subcategories'])) {
            $html .= "<div class='anidation-div' style='margin-left:{$level}em;'>";
            $html .= "<div class='flex'>";
            $html .= "<input type='checkbox' class='element-checkbox' id='{$category['uid']}'> ";
            $html .= "<label for='{$category['uid']}' class='element-label'>{$category['name']} </label> <button class='edit-btn' data-uid='{$category['uid']}'>{$icon_edit}</button>";
            $html .= "</div>";
            if ($category['description']) {
                $html .= " <p>{$category['description']}</p>";
            }
            $html .= "<div>";
            $html .= renderCategories($category['subcategories'], $level + 0);
            $html .= "</div>";
            $html .= '</div>';
        }
    }
    return $html;
}

/**
 * @return \App\Models\User|null
 */
function currentUser()
{
    return Auth::user();
}

function curl_call($url, $data = null, $headers = null, $method = 'GET')
{
    // Inicializa cURL
    $ch = curl_init($url);

    // Configura las opciones de cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    // Ejecuta la petición cURL
    $response = curl_exec($ch);

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (!in_array($statusCode, [200, 201])) {
        throw new \Exception('Error en la petición cURL: ' . $statusCode);
    }

    // Cierra la sesión cURL
    curl_close($ch);

    return $response;
}


function guzzle_call($url, $data = null, $headers = null, $method = 'GET')
{
    // Inicializa Guzzle
    $client = new Client();

    // Configura las opciones de Guzzle
    $options = [];
    if ($data) {
        $options['json'] = $data;
    }

    if ($headers) {
        $options['headers'] = $headers;
    }

    try {
        // Ejecuta la petición con Guzzle
        $response = $client->request($method, $url, $options);

        // Devuelve la respuesta
        return (string) $response->getBody();
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            // Obtiene la respuesta completa
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            throw new \Exception('Error en la petición Guzzle: ' . $statusCode . ' - ' . $body);
        } else {
            throw new \Exception('Error en la petición Guzzle: ' . $e->getMessage());
        }
    }
}

function add_timestamp_name_file($file)
{
    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    $extension = $file->getClientOriginalExtension();
    $timestamp = time();

    $filename = "{$originalName}-{$timestamp}.{$extension}";

    return $filename;
}
function generateToken($longitud = 64) {
    $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ()';
    $charLong = strlen($char);
    $token = '';

    for ($i = 0; $i < $longitud; $i++) {
        $randomIndex = random_int(0, $charLong - 1);
        $token .= $char[$randomIndex];
    }

    return $token;
}
