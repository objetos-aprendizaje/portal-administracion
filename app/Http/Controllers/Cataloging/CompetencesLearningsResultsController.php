<?php


namespace App\Http\Controllers\Cataloging;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\CompetencesModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use App\Models\LearningResultsModel;
use Illuminate\Http\Request;

class CompetencesLearningsResultsController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $competences_anidated = CompetencesModel::whereNull('parent_competence_uid')
            ->with(['subcompetences'])
            ->orderBy('name', 'ASC')
            ->get()->toArray();

        $competences = CompetencesModel::orderBy('name', 'ASC')->get()->toArray();

        return view(
            'cataloging.competences_learnings_results.index',
            [
                "page_name" => "Competencias y resultados de aprendizaje",
                "page_title" => "Competencias y resultados de aprendizaje",
                "resources" => [
                    "resources/js/cataloging_module/competences_learnings_results.js"
                ],
                "competences" => $competences,
                "competences_anidated" => $competences_anidated,
                "coloris" => true
            ]
        );
    }

    public function getAllCompetences()
    {

        $competences = CompetencesModel::whereNull('parent_competence_uid')->with('subcompetences')->get()->toArray();

        return response()->json($competences, 200);
    }

    public function getCompetence($competence_uid)
    {
        $competence = CompetencesModel::with('parentCompetence')->where('uid', $competence_uid)->first()->toArray();

        return response()->json($competence, 200);
    }


    /**
     * Obtiene todas las categorías.
     */
    public function getCompetences(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = CompetencesModel::with('parentCompetence');

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
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
     * Guarda una categoría. Si recibe un uid, actualiza la categoría con ese uid.
     */
    public function saveCompetence(Request $request)
    {
        $parent_competence_uid = $request->get('parent_competence_uid');
        $parent_competence = null;
        if ($parent_competence_uid) {
            $parent_competence = CompetencesModel::find($parent_competence_uid);
            if (!$parent_competence) {
                return response()->json(['errors' => ['parent_competence_uid' => ['La competencia padre no existe']]], 422);
            }
        }

        $messages = [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'is_multi_select.required' => 'Este campo es obligatorio.',
        ];

        $rules = [
            'name' => 'required|max:255',
            'description' => 'nullable',
            'parent_competence_uid' => 'nullable|exists:competences,uid',
            'is_multi_select' => 'required|boolean',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $competence_uid = $request->get('competence_uid');
        if ($competence_uid) {
            $isNew = false;
            $competence = CompetencesModel::find($competence_uid);
        } else {
            $isNew = true;
            $competence = new CompetencesModel();
            $competence->uid = generate_uuid();
        }

        $competence->name = $request->get('name');
        $competence->description = $request->get('description');
        $competence->parent_competence_uid = $parent_competence_uid;
        $competence->is_multi_select = $request->get('is_multi_select');

        $messageLog = $isNew ? 'Competencia añadida' : 'Competencia actualizada';

        DB::transaction(function () use ($competence, $messageLog) {
            $competence->save();
            LogsController::createLog($messageLog, 'Competencias', auth()->user()->uid);
        });

        return response()->json(['message' => $isNew ? 'Competencia añadida correctamente' : 'Competencia modificada correctamente'], 200);
    }

    public function getListCompetences(Request $request)
    {
        $search = $request->input("search");


        $query = CompetencesModel::whereNull('parent_competence_uid')
            ->with(['subcompetences'])
            ->orderBy('name', 'ASC');

        // Si se proporcionó un término de búsqueda, lo aplicamos
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Ahora ejecutamos la consulta y obtenemos los resultados
        $competences = $query->get()->toArray();

        $html = view('cataloging.competences_learnings_results.competences', ['competences' => $competences, 'first_loop' => true])->render();

        return response()->json(['html' => $html]);
    }

    public function deleteCompetencesLearningResults(Request $request)
    {
        $uids = $request->input('uids');

        DB::transaction(function () use ($uids) {
            LearningResultsModel::destroy($uids["learningResults"]);
            CompetencesModel::destroy($uids["competences"]);
            LogsController::createLog("Eliminación de competencias", 'Competencias', auth()->user()->uid);
            LogsController::createLog("Eliminación de resultados de aprendizaje", 'Resultados de aprendizaje', auth()->user()->uid);
        });

        return response()->json(['message' => 'Elementos eliminados correctamente'], 200);
    }

    public function saveLearningResult(Request $request)
    {

        $this->validateLearningResult($request);

        $competence_uid = $request->get('competence_uid');
        $learning_result_uid = $request->get('learning_result_uid');

        if (!$learning_result_uid) {
            $learningResult = new LearningResultsModel();
            $learningResult->uid = generate_uuid();
            $learningResult->competence_uid = $competence_uid;
        } else {
            $learningResult = LearningResultsModel::find($learning_result_uid);
        }

        $learningResult->name = $request->get('name');
        $learningResult->description = $request->get('description');
        $learningResult->save();

        return response()->json(['message' => 'Resultado de aprendizaje guardado correctamente'], 200);
    }

    private function validateLearningResult(Request $request)
    {
        $messages = [
            'name.required' => 'El nombre es obligatorio.',
        ];

        $rules = [
            'name' => 'required|max:255'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    }

    public function getLearningResult($learningResultUid)
    {
        $learningResult = LearningResultsModel::where('uid', $learningResultUid)->first();

        return response()->json($learningResult, 200);
    }
}
