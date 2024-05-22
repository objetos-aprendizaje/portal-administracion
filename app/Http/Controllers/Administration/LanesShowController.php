<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;

class LanesShowController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        return view(
            'administration.lanes_show',
            [
                "page_name" => "Carriles a mostrar",
                "page_title" => "Carriles a mostrar",
                "resources" => [
                    "resources/js/administration_module/lanes_show.js"
                ],
            ]
        );
    }

    public function saveLanesShow(Request $request)
    {

        $updateData = [
            'lane_recents_courses' => $request->input('lane_recents_courses'),
            'lane_recents_educational_programs' => $request->input('lane_recents_educational_programs'),
            'lane_recents_educational_resources' => $request->input('lane_recents_educational_resources'),
            'lane_recents_itineraries' => $request->input('lane_recents_itineraries'),
        ];

        DB::transaction(function () use ($updateData) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            LogsController::createLog('Actualización de carriles a mostrar', 'Carriles a mostrar', auth()->user()->uid);
        });

        return response()->json(['message' => 'Preferencias de carriles actualizados correctamente']);
    }
}
