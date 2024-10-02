<?php

namespace App\Http\Middleware;

use App\Exceptions\OperationFailedException;
use App\Models\ApiKeysModel;
use Closure;
use Illuminate\Http\Request;

class CheckApiKeyFront
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

        if($apiKey != env("API_KEY_FRONT")){
            throw new OperationFailedException('Not authorized', 401);
        }

        return $next($request);
    }
}
