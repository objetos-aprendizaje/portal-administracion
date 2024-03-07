<?php

namespace App\Http\Middleware;
use Closure;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\View;

/**
 * Envía a todas las vistas las opciones generales
 */
class GeneralOptionsMiddleware
{

    /**
     * Comparte con los controladores y las vistas las opciones generales
     */
    public function handle($request, Closure $next)
    {
        $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();

        app()->instance('general_options', $general_options);

        View::share('general_options', $general_options);

        return $next($request);
    }
}
