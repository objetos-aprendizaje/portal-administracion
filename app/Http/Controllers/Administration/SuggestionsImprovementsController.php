<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\SuggestionSubmissionEmailsModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;

class SuggestionsImprovementsController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $emailsSuggestions = SuggestionSubmissionEmailsModel::orderBy('created_at', 'desc')->get()->toArray();

        return view(
            'administration.suggestions_improvements',
            [
                "page_name" => "Sugerencias y mejoras",
                "page_title" => "Sugerencias y mejoras",
                "resources" => [
                    "resources/js/administration_module/suggestions_improvements.js",
                ],
                "emails_suggestions" => $emailsSuggestions,
                "tabulator" => true,
                "submenuselected" => "suggestions-improvements",
            ]
        );
    }

    public function saveEmail(Request $request)
    {

        // Comprobamos si el email ya existe
        $email = $request->input('email');


        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'El email es inválido'], 406);
        }

        $existEmail = SuggestionSubmissionEmailsModel::where('email', $email)->first();

        if ($existEmail) return response()->json(['message' => 'El email ya existe'], 406);

        $emailEnvioSugerencias = new SuggestionSubmissionEmailsModel();
        $uid = generateUuid();
        $emailEnvioSugerencias->uid = $uid;
        $emailEnvioSugerencias->email = $email;

        DB::transaction(function () use ($emailEnvioSugerencias) {
            $emailEnvioSugerencias->save();
            LogsController::createLog('Añadido de email de sugerencias: ' . $emailEnvioSugerencias->email, 'Sugerencias y mejoras', auth()->user()->uid);
        });

        return response()->json(['message' => 'Email añadido correctamente', 'uid_email_inserted' => $uid]);
    }

    public function getEmails(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = SuggestionSubmissionEmailsModel::query();

        if ($search) {
            $query->where('email', 'ILIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $data = $query->paginate($size);

        return response()->json($data);
    }

    /**
     * Recibe un array de uids de emails y los elimina de la base de datos.
     */
    public function deleteEmails(Request $request)
    {
        $uidsEmails = $request->input('uidsEmails');

        $suggestionsSubmissionEmails = SuggestionSubmissionEmailsModel::whereIn('uid', $uidsEmails)->get();
        DB::transaction(function () use ($suggestionsSubmissionEmails) {
            foreach($suggestionsSubmissionEmails as $suggestionEmail) {
                $suggestionEmail->delete();
                LogsController::createLog('Eliminar email de sugerencias: ' . $suggestionEmail->email, 'Sugerencias y mejoras', auth()->user()->uid);
            }
        });

        return response()->json(['message' => 'Emails eliminados correctamente']);
    }
}
