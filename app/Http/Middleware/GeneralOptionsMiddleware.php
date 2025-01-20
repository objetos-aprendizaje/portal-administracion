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
        $generalOptions = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();

        app()->instance('general_options', $generalOptions);

        View::share('general_options', $generalOptions);

        $tooltipTexts = TooltipTextsModel::get();

        View::share('tooltip_texts', $tooltipTexts);

        return $next($request);


    }
}
