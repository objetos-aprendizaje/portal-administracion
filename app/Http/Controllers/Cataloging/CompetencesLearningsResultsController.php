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
use Exception;
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

        $competences = CompetencesModel::with('subcompetences')->get()->toArray();

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
            ->orderBy('name', 'ASC')
            ->get()->toArray();

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

    public function importEscoFramework(Request $request)
    {

        $messages = [
            'skills_hierarchy_file.required' => 'El fichero skills_hierarchy es obligatorio',
            'skills_file.max' => 'El fichero skills_file es obligatorio',
            'broader_relations_skill_pillar_file.required' => 'El fichero broader_relations_skill_pillar es obligatorio',
        ];

        $rules = [
            'skills_hierarchy_file' => 'required|file',
            'skills_file' => 'required|file',
            'broader_relations_skill_pillar_file' => 'required|file',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $skills_hierarchy_file = $request->file('skills_hierarchy_file');
        $skills_hierarchy = $this->csvFileToObject($skills_hierarchy_file);


        $hierarchy = $this->buildHierarchy($skills_hierarchy);

        foreach($hierarchy as $h) {
            CompetencesModel::create($h);

        }


        $skills_file = $request->file('skills_file');
        $skills = $this->csvFileToObject($skills_file);

        $broader_relations_skill_pillar_file = $request->file('broader_relations_skill_pillar_file');
        $broader_relations_skill_pillar = $this->csvFileToObject($broader_relations_skill_pillar_file);


        $hierarchyCompetences = [];
        $hierarchySkills = [];


        //recorremos las skills
        foreach($skills as $skill) {

            // recorremos el fichero que relaciona resultados y competencias
            foreach($broader_relations_skill_pillar as $broader_relations) {

                //verificamos que existan los datos necesarios
                if (isset($broader_relations[1]) && isset($skill[1])){

                    //localizamos el dato
                    if($broader_relations[1] == $skill[1]) {

                        //comprobamos que $skill tiene hijos con el broarder
                        $has_children = $this->hasChildren($broader_relations_skill_pillar, $skill[1]);

                        //En este caso estas skills que tienen hijos, se han de considerar como competencias para almacenarlas así.
                        if ($has_children == true){

                            $result = $this->buildHierarchyCompetences($broader_relations[3], $skill);

                            $hierarchyCompetences = array_merge($hierarchyCompetences, $result);

                        }
                    }
                }
            }
        }

        //almacenamos en base de datos
        foreach($hierarchyCompetences as $h) {
            CompetencesModel::create($h);
        }

        //recorremos las skills
        foreach($skills as $skill) {

            // recorremos el fichero que relaciona resultados y competencias
            foreach($broader_relations_skill_pillar as $broader_relations) {

                //verificamos que existan los datos necesarios
                if (isset($broader_relations[1]) && isset($skill[1])){

                    if($broader_relations[1] == $skill[1]) {

                        //comprobamos que $skill no tiene hijos con el broarder
                        $has_children = $this->hasChildren($broader_relations_skill_pillar, $skill[1]);


                        //En este caso ya no tienen hijos entonces son resultados de aprendizaje.
                        if (!$has_children){

                            $result = $this->buildHierarchySkills($broader_relations[3], $skill);

                            $hierarchySkills = array_merge($hierarchySkills, $result);

                        }
                    }
                }
            }
        }


        //almacenamos en base de datos
        foreach($hierarchySkills as $h) {
            LearningResultsModel::create($h);
        }

        return response()->json(['message' => 'Competencias y resultados de aprendizaje añadidos'], 200);

    }


    private function searchFatherCompetence($uid, $broader_relations_skill_pillar) {
        foreach($broader_relations_skill_pillar as $row) {
            if($this->extractuidUrl($row[1]) == $uid) {
                return $row;
            }
        }
    }


    private function buildHierarchy($data)
    {
        $hierarchy = [];

        $parent_level_0_uuid = "";
        $parent_level_1_uuid = "";
        $parent_level_2_uuid = "";

        $uid_esco = generate_uuid();
        $hierarchy[] = [
            "uid" => $uid_esco,
            "name" => "ESCO",
            "description" => "",
            "parent_competence_uid" => NULL,
            'is_multi_select' => '1',
            'origin_code' => ""
        ];

        foreach ($data as $row) {
            // Si no está correcta la fila
            if (count($row) < 8) continue;

            if($row[0] != "" && $row[2] == "" && $row[4] == "" && $row[6] == "") {

                $level0_uid = generate_uuid();

                $hierarchy[] = [
                    "uid" => $level0_uid,
                    "name" => $row[1],
                    "description" => $row[8],
                    "parent_competence_uid" => $uid_esco,
                    'is_multi_select' => '1',
                    'origin_code' => $row[0]
                ];

                $parent_level_0_uuid = $level0_uid;


            }elseif ($row[0] != "" && $row[2] != "" && $row[4] == "" && $row[6] == ""){

                $level1_uid = generate_uuid();

                $hierarchy[] = [
                    "uid" => $level1_uid,
                    "name" => $row[3],
                    "description" => $row[8],
                    "parent_competence_uid" => $parent_level_0_uuid,
                    'is_multi_select' => '1',
                    'origin_code' => $row[2],
                ];

                $parent_level_1_uuid = $level1_uid;

            }elseif($row[0] != "" && $row[2] != "" && $row[4] != "" && $row[6] == ""){

                $level2_uid = generate_uuid();

                $hierarchy[] = [
                    "uid" => $level2_uid,
                    "name" => $row[5],
                    "description" => $row[8],
                    "parent_competence_uid" => $parent_level_1_uuid,
                    'is_multi_select' => '1',
                    'origin_code' => $row[4]
                ];

                $parent_level_2_uuid = $level2_uid;

            }elseif($row[0] != "" && $row[2] != "" && $row[4] != "" && $row[6] != ""){

                $level3_uid = generate_uuid();

                $hierarchy[] = [
                    "uid" => $level3_uid,
                    "name" => $row[7],
                    "description" => $row[8],
                    "parent_competence_uid" => $parent_level_2_uuid,
                    'is_multi_select' => '0',
                    'origin_code' => $row[6]
                ];

            }

        }

        return $hierarchy;
    }

    function isUuid($string) {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $string) === 1;
    }

    private function search_competence($value, $competences, $field)
    {
        $values = array_column($competences, $field);
        $index = array_search($value, $values);

        if($index) return $competences[$index];
        else return false;
    }

    private function extractuidUrl($url)
    {
        // Parse the URL into its components
        $components = parse_url($url);

        // Split the path into segments
        $segments = explode('/', $components['path']);

        // The uid is the last segment
        $uid = end($segments);

        return $uid;
    }


    private function csvFileToObject($file, $filter = [])
    {
        $data = [];
        $csvFile = new \SplFileObject($file->getRealPath());
        $csvFile->setFlags(\SplFileObject::READ_CSV);

        $isFirstLine = true;

        while (!$csvFile->eof()) {
            $row = $csvFile->fgetcsv();

            if ($isFirstLine) {
                $isFirstLine = false;
                continue;
            }

            if ($row) {
                $includeRow = true;

                foreach ($filter as $position => $value) {
                    if (!isset($row[$position]) || $row[$position] != $value) {
                        $includeRow = false;
                        break;
                    }
                }

                if ($includeRow) {
                    $data[] = $row;
                }
            }
        }

        return $data;
    }

    private function buildHierarchyCompetences($broader_relation, $skill){

        $hierarchyCompetences = [];

        $parent_uid = DB::table('competences')
        ->where('origin_code', $broader_relation)
        ->pluck('uid')->toArray();

        //dd($broader_relations, $skill, $parent_uid);

        foreach($parent_uid as $new_competence){

            $level4_uid = generate_uuid();

            $hierarchyCompetences[] = [
                "uid" => $level4_uid,
                "name" => $skill[4],
                "description" => $skill[12],
                "parent_competence_uid" => $new_competence,
                'is_multi_select' => '1',
                'origin_code' => $skill[1]
            ];
        }
        return $hierarchyCompetences;
    }
    private function buildHierarchySkills($broader_relation, $skill){

        $hierarchySkills = [];

        $parent_uid = DB::table('competences')
        ->where('origin_code', $broader_relation)
        ->pluck('uid')->toArray();

        //dd($broader_relations, $skill, $parent_uid);

        foreach($parent_uid as $new_competence){

            $level5_uid = generate_uuid();

            $hierarchySkills[] = [
                "uid" => $level5_uid,
                "name" => $skill[4],
                "description" => $skill[12],
                "competence_uid" => $new_competence,
                'is_multi_select' => '1',
                'origin_code' => $skill[1]
            ];
        }
        return $hierarchySkills;
    }
    private function hasChildren($broader_relations, $skill) {

        $has_children = false;

        foreach($broader_relations as $broader_relations_2) {
            if(!isset($broader_relations_2[3])) {
                continue;
            }
            if ($broader_relations_2[3] == $skill){

                $has_children = true;
                break;
            }
        }
        return $has_children;
    }


    function getSkillsWithChildren($broader_relations) {
        $resultados = [];

        foreach ($broader_relations as $subarray) {
            if (isset($subarray[3]) && !in_array($subarray[3], $resultados)) {
                $resultados[] = $subarray[3];
            }
        }

        return $resultados;
    }

    function getSkillsWithoutChildren($broader_relations) {
        $posicion1 = [];
        $posicion3 = [];

        // Recorre el array y llena los arrays de posición 1 y posición 3
        foreach ($broader_relations as $subarray) {
            if (isset($subarray[1])) {
                $posicion1[] = $subarray[1];
            }
            if (isset($subarray[3])) {
                $posicion3[] = $subarray[3];
            }
        }

        // Filtra los valores de posición 1 que no están en posición 3
        $resultado = array_filter($posicion1, function($valor) use ($posicion3) {
            return !in_array($valor, $posicion3);
        });

        // Elimina duplicados
        $resultado = array_unique($resultado);

        return $resultado;
    }

    function searchSkillFathers($broader_relations, $skill_uri) {
        $fathers = [];
        foreach($broader_relations as $relation) {
            if(!isset($relation[3])) {
                continue;
            }
            if($relation[1] == $skill_uri) $fathers[] = $relation[3];
        }

        return $fathers;
    }

    function searchInHierarchy($hierarchy, $uri) {
        $hierarchy_found = false;
        $i = 0;
        foreach($hierarchy as $h) {
            if($h["origin_code"] == $uri) $hierarchy_found = $h;
        }

        if($i > 1) {
            throw new Exception("más de un padre");
        }
        return $hierarchy_found;
    }
}
