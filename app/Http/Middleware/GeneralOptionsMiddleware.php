<?php

namespace App\Http\Middleware;
use Closure;
use App\Models\GeneralOptionsModel;
use App\Models\TooltipTextsModel;
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

        $tooltip_texts = TooltipTextsModel::get();

        View::share('tooltip_texts', $tooltip_texts);

        return $next($request);


    }
}
