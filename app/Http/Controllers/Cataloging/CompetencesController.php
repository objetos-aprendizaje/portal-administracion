<?php


namespace App\Http\Controllers\Cataloging;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\CompetencesModel;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;

class CompetencesController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $competences_anidated = CompetencesModel::whereNull('parent_competence_uid')->with('subcompetences')->orderBy('name', 'ASC')->get()->toArray();

        $competences = CompetencesModel::orderBy('name', 'ASC')->get()->toArray();

        return view(
            'cataloging.competences.index',
            [
                "page_name" => "Competencias",
                "page_title" => "Competencias",
                "resources" => [
                    "resources/js/cataloging_module/competences.js"
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
        $competences = CompetencesModel::with('parentCompetence')->get()->toArray();

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
            'is_multi_select.required' => 'El campo is_multi_select es obligatorio.',
        ];

        $rules = [
            'name' => 'required|max:255',
            'description' => 'nullable',
            'parent_competence_uid' => 'nullable|exists:competences,uid',
            'is_multi_select' => 'nullable|numeric|in:0,1',
        ];

        if (!$parent_competence || $parent_competence->is_multi_select) {
            $rules['is_multi_select'] = 'required|numeric|in:0,1';
        }

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
        $competence->is_multi_select = $parent_competence && !$parent_competence->is_multi_select ? null : $request->get('is_multi_select');

        $competence->save();

        return response()->json(['message' => $isNew ? 'Competencia añadida correctamente' : 'Competencia modificada correctamente'], 200);
    }

    public function getListCompetences(Request $request)
    {
        $search = $request->input("search");

        // Comenzamos la consulta sin ejecutarla
        $query = CompetencesModel::query();

        // Si se proporcionó un término de búsqueda, lo aplicamos
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        } else {
            $query->whereNull('parent_competence_uid')->with('subcompetences');
        }

        // Ahora ejecutamos la consulta y obtenemos los resultados
        $competences = $query->get()->toArray();

        // Asumiendo que 'renderCompetences' es una función que ya tienes para generar el HTML
        $html = renderCompetences($competences);

        return response()->json(['html' => $html]);
    }

    public function deleteCompetences(Request $request)
    {
        $uids = $request->input('uids');
        CompetencesModel::destroy($uids);
        return response()->json(['message' => 'Categorías eliminadas correctamente'], 200);
    }
}
