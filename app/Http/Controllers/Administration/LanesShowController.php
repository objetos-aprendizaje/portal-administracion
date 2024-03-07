<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\GeneralOptionsModel;

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

    public function saveLanesShow(Request $request) {

        $updateData = [
            'lane_recents_courses' => $request->input('lane_recents_courses'),
            'lane_recents_educational_programs' => $request->input('lane_recents_educational_programs'),
            'lane_recents_educational_resources' => $request->input('lane_recents_educational_resources'),
            'lane_recents_itineraries' => $request->input('lane_recents_itineraries'),
        ];

        foreach ($updateData as $key => $value) {
            GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
        }

        return response()->json(['message' => 'Preferencias de carriles actualizados correctamente']);

    }

}
