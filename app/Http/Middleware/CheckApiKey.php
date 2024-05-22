<?php

namespace App\Http\Middleware;

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
        return $next($request);

        $apiKey = $request->header('API_KEY');

        if (!$apiKey) {
            // Si no se encuentra la cabecera API_KEY, devuelve una respuesta con un código de estado 401
            return response()->json(['error' => 'API Key not found'], 401);
        }

        // Si se encuentra la cabecera API_KEY, continúa con la petición
        return $next($request);
    }
}
