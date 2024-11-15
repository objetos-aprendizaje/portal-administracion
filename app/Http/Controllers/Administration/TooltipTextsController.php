<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Logs\LogsController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\TooltipTextsModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TooltipTextsController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {


        return view(
            'administration.tooltip_texts.index',
            [
                "page_name" => "Textos para tooltips",
                "page_title" => "Textos para tooltips",
                "resources" => [
                    "resources/js/administration_module/tooltip_texts.js"
                ],
                "tabulator" => true,
                'submenuselected' => 'administracion-tooltip-texts',
            ]
        );
    }


    public function getAllTooltipTexts(Request $request)
    {

        $query = TooltipTextsModel::get();

        return response()->json($query, 200);
    }

    /**
     * Obtiene todas las licencias.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTooltipTexts(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = TooltipTextsModel::query();

        if ($search) {
            $query->where('description', 'ILIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    /**
     * Obtiene una licencia específico basada en su UID.
     *
     * @param  string $center_uid El UID del centro.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTooltipText($tooltip_text_uid)
    {
        $tooltip_text = TooltipTextsModel::where('uid', $tooltip_text_uid)->first()->toArray();
        return response()->json($tooltip_text);
    }

    /**
     * Crea una nueva licencia.
     *
     * @param  \Illuminate\Http\Request  $request Los datos de la nueva licencia.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveTooltipText(Request $request)
    {
        $messages = [
            'form_id.required' => 'El nombre es obligatorio.',
            'input_id.required' => 'El nombre es obligatorio.',
            'description.required' => 'La descripción es obligatorio.',
        ];

        $validator = Validator::make($request->all(), [
            'form_id' => 'required',
            'input_id' => 'required',
            'description' => 'required',
        ], $messages);


        if ($validator->fails()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validator->errors()], 422);
        }

        $tooltip_text_uid = $request->input("tooltip_text_uid");

        return DB::transaction(function () use ($request, $tooltip_text_uid) {

            if (!$tooltip_text_uid) {
                $tooltip_text = new TooltipTextsModel();
                $tooltip_uid = generate_uuid();
                $tooltip_text->uid = $tooltip_uid;
                $isNew = true;
            } else {
                $tooltip_text = TooltipTextsModel::find($tooltip_text_uid);
                $isNew = false;
            }

            $tooltip_text->fill($request->only([
                'form_id',
                'input_id',
                'description'
            ]));

            $tooltip_text->save();

            LogsController::createLog('Añadir texto de tooltip', 'Configuración General', auth()->user()->uid);

            return response()->json(['message' => $isNew ? 'Texto de tooltip añadido correctamente' : 'Texto de tooltip actualizada correctamente']);
        }, 5);
    }

    /**
     * Elimina un centro específico.
     *
     * @param  string $uids Array de uids de sistemas centro.
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteTooltipTexts(Request $request)
    {
        $uids = $request->input('uids');


        DB::transaction(function () use ($uids) {
            TooltipTextsModel::destroy($uids);
            LogsController::createLog('Eliminar texto de tooltip', 'Configuración General', auth()->user()->uid);
        }, 5);

        return response()->json(['message' => 'Textos de tooltip eliminados correctamente']);
    }

}
