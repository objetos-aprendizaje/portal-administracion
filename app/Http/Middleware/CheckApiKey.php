<?php

namespace App\Http\Middleware;

use App\Exceptions\OperationFailedException;
use App\Models\ApiKeysModel;
use Closure;
use Illuminate\Http\Request;

class CheckApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $apiKey = $request->header('API-KEY');

        $apiKeyExistsBd = ApiKeysModel::where('api_key', $apiKey)->exists();

        if (!$apiKeyExistsBd) {
            // Si no se encuentra la cabecera API_KEY, devuelve una respuesta con un código de estado 401
            throw new OperationFailedException('API Key not found', 401);
        }

        // Si se encuentra la cabecera API_KEY, continúa con la petición
        return $next($request);
    }
}
