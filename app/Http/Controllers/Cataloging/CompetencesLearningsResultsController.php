<?php


namespace App\Http\Controllers\Cataloging;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\CompetencesModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use App\Models\CompetenceFrameworksModel;
use App\Models\LearningResultsModel;
use Exception;
use Illuminate\Http\Request;
use App\Models\CompetenceFrameworksLevelsModel;

class CompetencesLearningsResultsController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        return view(
            'cataloging.competences_learnings_results.index',
            [
                "page_name" => "Competencias y resultados de aprendizaje",
                "page_title" => "Competencias y resultados de aprendizaje",
                "resources" => [
                    "resources/js/cataloging_module/competences_learnings_results.js"
                ],
                "coloris" => true,
                "submenuselected" => "cataloging-competences-learning-results",
                "infiniteTree" => true
            ]
        );
    }

    public function getAllCompetences()
    {
        $competenceFrameworks = CompetenceFrameworksModel::with([
            'levels',
            'competences',
            'competences.learningResults',
            'competences.allSubcompetences',
            'competences.allSubcompetences.learningResults'
        ])->get();

        return response()->json($competenceFrameworks, 200);
    }

    public function searchLearningResults($query)
    {
        $learningResults = LearningResultsModel::where('name', 'ILIKE', '%' . $query . '%')->select("uid", "name")->get();

        return response()->json($learningResults);
    }

    public function getCompetence($competenceUid)
    {
        $competence = CompetencesModel::with('parentCompetence')->where('uid', $competenceUid)->first()->toArray();

        return response()->json($competence, 200);
    }

    public function getCompetenceFramework($competenceFrameworkUid)
    {
        $competenceFramework = CompetenceFrameworksModel::with('levels')->where('uid', $competenceFrameworkUid)->first();

        return response()->json($competenceFramework, 200);
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
            $query->where('name', 'ILIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    public function saveCompetenceFramework(Request $request)
    {
        $messages = [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
        ];

        $rules = [
            'name' => 'required|max:255',
            'description' => 'nullable',
            'competence_framework_uid' => 'nullable|exists:competence_frameworks,uid',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validator->errors()], 422);
        }

        $competenceFrameworkUid = $request->get('competence_framework_modal_uid');

        if ($competenceFrameworkUid) {
            $isNew = false;
            $competenceFramework = CompetenceFrameworksModel::find($competenceFrameworkUid);
        } else {
            $isNew = true;
            $competenceFramework = new CompetenceFrameworksModel();
            $competenceFramework->uid = generateUuid();
        }

        $competenceFramework->fill($request->only([
            'name',
            'has_levels',
            'description'
        ]));

        DB::transaction(function () use ($isNew, $request, $competenceFramework) {
            $competenceUid = $competenceFramework->uid;
            $competenceFramework->save();
            if ($isNew && $request->get('has_levels') == "1") {
                $this->saveLevels($competenceUid, $request['levels']);
            }
            if (!$isNew && $request->get('has_levels') == "0") {
                CompetenceFrameworksLevelsModel::where('competence_framework_uid', $competenceUid)->delete();
            }
            if (!$isNew && $request->get('has_levels') == "1") {
                $this->saveLevels($competenceUid, $request['levels']);
            }

            $messageLog = $isNew ? 'Marco de competencias añadido: ' : 'Marco de competencias actualizado: ';
            $messageLog .= $competenceFramework->name;

            LogsController::createLog($messageLog, 'Marcos de competencias', auth()->user()->uid);
        });

        return response()->json(['message' => $isNew ? 'Marco de competencias añadido correctamente' : 'Marco de competencias modificado correctamente'], 200);
    }

    public function saveLevels($uid, $levels)
    {

        $levels = json_decode($levels, true);

        $oldLevels = CompetenceFrameworksLevelsModel::where('competence_framework_uid', $uid)->get()->toArray();

        if (count($levels) < count($oldLevels)) {
            $uidsArray1 = array_column($levels, 'uid');
            $uidsArray2 = array_column($oldLevels, 'uid');

            // Paso 2: Encontrar los UID que no están en ambos arrays
            $uidsOnlyInArray1 = array_diff($uidsArray1, $uidsArray2);
            $uidsOnlyInArray2 = array_diff($uidsArray2, $uidsArray1);

            // Unir los resultados para obtener los UIDs que no están en ambos arrays
            $uidsNotInBoth = array_merge($uidsOnlyInArray1, $uidsOnlyInArray2);

            foreach ($uidsNotInBoth as $todelete) {
                CompetenceFrameworksLevelsModel::where('uid', $todelete)->delete();
            }
        }

        foreach ($levels as $level) {

            if ($level['uid']) {
                $levelData = CompetenceFrameworksLevelsModel::find($level['uid']);
            } else {
                $levelData = new CompetenceFrameworksLevelsModel();
                $levelData->uid = generateUuid();
            }

            $levelData->competence_framework_uid = $uid;
            $levelData->name = $level['name'];
            $levelData->origin_code = "";

            $levelData->save();
        }
    }

    /**
     * Guarda una categoría. Si recibe un uid, actualiza la categoría con ese uid.
     */
    public function saveCompetence(Request $request)
    {

        $messages = [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
        ];

        $rules = [
            'name' => 'required|max:255',
            'description' => 'nullable',
            'parent_competence_uid' => 'nullable|exists:competences,uid',
            'competence_framework_uid' => 'nullable|exists:competence_frameworks,uid',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validator->errors()], 422);
        }

        $competenceUid = $request->get('competence_uid');
        if ($competenceUid) {
            $isNew = false;
            $competence = CompetencesModel::find($competenceUid);
        } else {
            $isNew = true;
            $competence = new CompetencesModel();
            $competence->uid = generateUuid();
        }

        $competence->fill($request->only([
            'name',
            'description',
            'parent_competence_uid',
            'competence_framework_uid'
        ]));

        $messageLog = $isNew ? 'Competencia añadida: ' : 'Competencia actualizada: ';
        $messageLog .= $competence->name;

        DB::transaction(function () use ($competence, $messageLog) {
            $competence->save();
            LogsController::createLog($messageLog, 'Competencias', auth()->user()->uid);
        });

        return response()->json(['message' => $isNew ? 'Competencia añadida correctamente' : 'Competencia modificada correctamente'], 200);
    }

    public function deleteCompetencesLearningResults(Request $request)
    {
        $uids = $request->input('uids');

        DB::transaction(function () use ($uids) {
            CompetenceFrameworksModel::destroy($uids["competencesFrameworks"]);
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

        $competenceUid = $request->get('competence_uid');
        $learningResultUid = $request->get('learning_result_uid');

        if (!$learningResultUid) {
            $learningResult = new LearningResultsModel();
            $learningResult->uid = generateUuid();
            $learningResult->competence_uid = $competenceUid;
        } else {
            $learningResult = LearningResultsModel::find($learningResultUid);
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
            throw new \Illuminate\Validation\ValidationException($validator);
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

        $skillsHierarchyFile = $request->file('skills_hierarchy_file');
        $skillsHierarchy = $this->csvFileToObject($skillsHierarchyFile);

        DB::transaction(function () use ($skillsHierarchy, $request) {
            $hierarchy = $this->buildHierarchy($skillsHierarchy);

            foreach ($hierarchy as $h) {
                CompetencesModel::create($h);
            }

            $skillsFile = $request->file('skills_file');
            $skills = $this->csvFileToObject($skillsFile);

            $broaderRelationsSkillPillarFile = $request->file('broader_relations_skill_pillar_file');
            $broaderRelationsSkillPillar = $this->csvFileToObject($broaderRelationsSkillPillarFile);

            $hierarchyCompetences = [];
            $hierarchySkills = [];

            //recorremos las skills
            foreach ($skills as $skill) {

                // recorremos el fichero que relaciona resultados y competencias
                foreach ($broaderRelationsSkillPillar as $broaderRelations) {

                    //verificamos que existan los datos necesarios
                    if (isset($broaderRelations[1]) && isset($skill[1]) && $broaderRelations[1] == $skill[1]) {

                        //comprobamos que $skill tiene hijos con el broarder
                        $hasChildren = $this->hasChildren($broaderRelationsSkillPillar, $skill[1]);

                        //En este caso estas skills que tienen hijos, se han de considerar como competencias para almacenarlas así.
                        if ($hasChildren) {

                            $result = $this->buildHierarchyCompetences($broaderRelations[3], $skill);

                            $hierarchyCompetences = array_merge($hierarchyCompetences, $result);
                        }
                    }
                }
            }

            //almacenamos en base de datos
            foreach ($hierarchyCompetences as $h) {
                CompetencesModel::create($h);
            }

            //recorremos las skills
            foreach ($skills as $skill) {

                // recorremos el fichero que relaciona resultados y competencias
                foreach ($broaderRelationsSkillPillar as $broaderRelations) {

                    //verificamos que existan los datos necesarios
                    if (isset($broaderRelations[1]) && isset($skill[1]) && $broaderRelations[1] == $skill[1]) {
                        //comprobamos que $skill no tiene hijos con el broarder
                        $hasChildren = $this->hasChildren($broaderRelationsSkillPillar, $skill[1]);


                        //En este caso ya no tienen hijos entonces son resultados de aprendizaje.
                        if (!$hasChildren) {

                            $result = $this->buildHierarchySkills($broaderRelations[3], $skill);

                            $hierarchySkills = array_merge($hierarchySkills, $result);
                        }
                    }
                }
            }

            //almacenamos en base de datos
            foreach ($hierarchySkills as $h) {
                LearningResultsModel::create($h);
            }
        });

        return response()->json(['message' => 'Competencias y resultados de aprendizaje añadidos'], 200);
    }

    private function buildHierarchy($data)
    {
        $hierarchy = [];

        $parentLevel_0Uuid = "";
        $parentLevel_1Uuid = "";
        $parentLevel_2Uuid = "";

        $uidEsco = $this->createEsco();

        foreach ($data as $row) {
            // Si no está correcta la fila
            if (count($row) < 8) {
                continue;
            }

            if ($row[0] != "" && $row[2] == "" && $row[4] == "" && $row[6] == "") {

                $level0Uid = generateUuid();

                $hierarchy[] = [
                    "uid" => $level0Uid,
                    "name" => $row[1],
                    "description" => $row[8],
                    'origin_code' => $row[0],
                    'competence_framework_uid' => $uidEsco,
                ];

                $parentLevel_0Uuid = $level0Uid;
            } elseif ($row[0] != "" && $row[2] != "" && $row[4] == "" && $row[6] == "") {

                $level1Uid = generateUuid();

                $hierarchy[] = [
                    "uid" => $level1Uid,
                    "name" => $row[3],
                    "description" => $row[8],
                    "parent_competence_uid" => $parentLevel_0Uuid,
                    'origin_code' => $row[2],
                ];

                $parentLevel_1Uuid = $level1Uid;
            } elseif ($row[0] != "" && $row[2] != "" && $row[4] != "" && $row[6] == "") {

                $level2Uid = generateUuid();

                $hierarchy[] = [
                    "uid" => $level2Uid,
                    "name" => $row[5],
                    "description" => $row[8],
                    "parent_competence_uid" => $parentLevel_1Uuid,
                    'origin_code' => $row[4]
                ];

                $parentLevel_2Uuid = $level2Uid;
            } elseif ($row[0] != "" && $row[2] != "" && $row[4] != "" && $row[6] != "") {

                $level3Uid = generateUuid();

                $hierarchy[] = [
                    "uid" => $level3Uid,
                    "name" => $row[7],
                    "description" => $row[8],
                    "parent_competence_uid" => $parentLevel_2Uuid,
                    'origin_code' => $row[6]
                ];
            }
        }

        return $hierarchy;
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

    private function buildHierarchyCompetences($broaderRelation, $skill)
    {

        $hierarchyCompetences = [];

        $parentUid = DB::table('competences')
            ->where('origin_code', $broaderRelation)
            ->pluck('uid')->toArray();

        foreach ($parentUid as $newCompetence) {

            $level4Uid = generateUuid();

            $hierarchyCompetences[] = [
                "uid" => $level4Uid,
                "name" => $skill[4],
                "description" => $skill[12],
                "parent_competence_uid" => $newCompetence,
                'origin_code' => $skill[1]
            ];
        }
        return $hierarchyCompetences;
    }
    private function buildHierarchySkills($broaderRelation, $skill)
    {

        $hierarchySkills = [];

        $parentUid = DB::table('competences')
            ->where('origin_code', $broaderRelation)
            ->pluck('uid')->toArray();

        foreach ($parentUid as $newCompetence) {

            $level5Uid = generateUuid();

            $hierarchySkills[] = [
                "uid" => $level5Uid,
                "name" => $skill[4],
                "description" => $skill[12],
                "competence_uid" => $newCompetence,
                'origin_code' => $skill[1]
            ];
        }
        return $hierarchySkills;
    }
    private function hasChildren($broaderRelations, $skill)
    {

        $hasChildren = false;

        foreach ($broaderRelations as $broaderRelations_2) {

            if (!isset($broaderRelations_2[3])) {

                continue;
            }
            if ($broaderRelations_2[3] == $skill) {

                $hasChildren = true;
                break;
            }
        }
        return $hasChildren;
    }

    public function exportCSV()
    {
        $competencesAnidated = CompetencesModel::whereNull('parent_competence_uid')
            ->with(['allsubcompetences'])
            ->orderBy('name', 'ASC')
            ->get()->toArray();

        return response()->json($competencesAnidated, 200);
    }

    public function importCSV(Request $request)
    {

        $messages = [
            'data-json-import.required' => 'El fichero es obligatorio',
        ];

        $rules = [
            'data-json-import' => 'required|file',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'No ha seleccionado ningún fichero'], 422);
        }


        $file = $request->file('data-json-import');

        $jsonContent = file_get_contents($file->getRealPath());

        $data = json_decode($jsonContent, true);

        DB::beginTransaction();

        $this->createEsco();

        try {
            foreach ($data as $item) {
                $this->importCompetence($item);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Importación realizada']);
    }

    private function importCompetence($item)
    {

        $firstUid = generateUuid();

        if (isset($item['allsubcompetences']) && $item['allsubcompetences'] != null) {
            foreach ($item['allsubcompetences'] as $sub) {
                $this->importCompetence($sub);
            }
        }
        if (isset($item['all_subcompetences']) && $item['all_subcompetences'] != null) {
            foreach ($item['allsubcompetences'] as $sub) {
                $this->importCompetence($sub);
            }
        }

        if (isset($item['learning_results']) && $item['learning_results'] != null) {

            foreach ($item['learning_results'] as $result) {

                $secondUid = generateUuid();

                LearningResultsModel::create([
                    'uid' => $secondUid,
                    'name' => $result['name'],
                    'description' => $item['description'] ?? '',
                    'competence_uid' => $firstUid,
                    'origin_code' => $item['origin_code'] ?? '',
                ]);
            }
        }
    }

    private function createEsco()
    {

        $competence_framework_uid = generateUuid();

        CompetenceFrameworksModel::create([
            'uid' => $competence_framework_uid,
            'has_level' => 1,
            'name' => "ESCO",
            'description' => '',
            'origin_code' => '',
        ]);

        return $competence_framework_uid;

    }
}
