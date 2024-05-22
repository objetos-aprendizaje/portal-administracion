<?php

namespace App\Http\Controllers\LearningObjects;

use App\Models\CallsModel;
use App\Models\CoursesModel;
use Illuminate\Routing\Controller as BaseController;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramTypesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;


class EducationalProgramsController extends BaseController
{
    public function index()
    {

        $calls = CallsModel::all()->toArray();
        $educational_program_types = EducationalProgramTypesModel::all()->toArray();

        return view(
            'learning_objects.educational_programs.index',
            [
                "page_name" => "Listado de programas formativos",
                "page_title" => "Listado de programas formativos",
                "resources" => [
                    "resources/js/learning_objects_module/educational_programs.js"
                ],
                "tabulator" => true,
                "calls" => $calls,
                "educational_program_types" => $educational_program_types,
                "tomselect" => true
            ]
        );
    }

    public function getEducationalPrograms(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = EducationalProgramsModel::join("educational_program_types as educational_program_type", "educational_program_type.uid", "=", "educational_programs.educational_program_type_uid", "left")->join("calls", "educational_programs.call_uid", "=", "calls.uid", "left");

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('educational_programs.name', 'LIKE', "%{$search}%")
                    ->orWhere('educational_programs.description', 'LIKE', "%{$search}%");
            });
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $query->select("educational_programs.*", "educational_program_type.name as educational_program_type_name", "calls.name as call_name");
        $data = $query->paginate($size);
        return response()->json($data, 200);
    }

    /**
     * Crea una nueva convocatoria.
     *
     * @param  \Illuminate\Http\Request  $request Los datos de la nueva convocatoria.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveEducationalProgram(Request $request)
    {

        $errors = $this->validateEducationalProgram($request);

        if ($errors->any()) {
            return response()->json(['errors' => $errors], 400);
        }

        $educational_program_uid = $request->input("educational_program_uid");

        if ($educational_program_uid) {
            $educational_program = EducationalProgramsModel::find($educational_program_uid);
            $isNew = false;
        } else {
            $educational_program = new EducationalProgramsModel();
            $educational_program_uid = generate_uuid();
            $educational_program->uid = $educational_program_uid;
            $isNew = true;
        }

        DB::transaction(function () use ($request, &$isNew, $educational_program) {
            $this->handleImageUpload($request, $educational_program);
            $this->fillEducationalProgram($request, $educational_program);
            $this->handleCourses($request, $educational_program);
            $this->logAction($isNew);
        });

        return response()->json(['message' => $isNew ? 'Programa formativo añadido correctamente' : 'Programa formativo actualizado correctamente']);
    }

    private function handleImageUpload($request, $educational_program)
    {
        if ($request->file('image_path')) {
            $file = $request->file('image_path');
            $path = 'images/educational-programs-images';

            $destinationPath = public_path($path);

            $filename = add_timestamp_name_file($file);

            $file->move($destinationPath, $filename);

            $educational_program->image_path = $path . "/" . $filename;
        }
    }

    private function fillEducationalProgram($request, $educational_program)
    {
        $educational_program->fill($request->only([
            'name', 'description', 'educational_program_type_uid', 'call_uid', 'inscription_start_date', 'inscription_finish_date', 'is_modular',
        ]));

        $educational_program->save();
    }

    private function handleCourses($request, $educational_program)
    {
        $courses = $request->input('courses');

        if ($courses) {
            CoursesModel::whereIn('uid', $courses)->update(['educational_program_uid' => $educational_program->uid]);
            CoursesModel::whereNotIn('uid', $courses)->where('educational_program_uid', $educational_program->uid)->update(['educational_program_uid' => null]);
        } else {
            CoursesModel::where('educational_program_uid', $educational_program->uid)->update(['educational_program_uid' => null]);
        }
    }

    private function logAction($isNew)
    {
        $logMessage = $isNew ? 'Programa formativo añadido' : 'Programa formativo actualizado';
        LogsController::createLog($logMessage, 'Programas educativos', auth()->user()->uid);
    }

    private function validateEducationalProgram($request)
    {
        $messages = [
            'name.required' => 'El nombre es obligatorio',
            'name.max' => 'El nombre no puede tener más de 255 caracteres',
            'educational_program_type_uid.required' => 'El tipo de programa educativo es obligatorio',
            'inscription_start_date.required' => 'La fecha de inicio de inscripción es obligatoria',
            'inscription_finish_date.required' => 'La fecha de fin de inscripción es obligatoria',
            'inscription_start_date.after_or_equal' => 'La fecha de inicio de inscripción no puede ser anterior a la fecha y hora actual.',
            'inscription_finish_date.after_or_equal' => 'La fecha de fin de inscripción no puede ser anterior a la fecha de inicio de inscripción.',
            'is_modular.required' => 'El campo es modular es obligatorio',
        ];

        $rules = [
            'name' => 'required|max:255',
            'educational_program_type_uid' => 'required',
            'inscription_start_date' => 'required|date',
            'inscription_finish_date' => 'required|date|after_or_equal:today',
            'is_modular' => 'required'
        ];

        $educational_program_uid = $request->input("educational_program_uid");

        if (!$educational_program_uid) {
            $rules['inscription_start_date'] = 'after_or_equal:now';
            $rules['inscription_finish_date'] = 'after_or_equal:inscription_start_date';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        $errorsValidator = $validator->errors();

        return $errorsValidator;
    }

    /**
     * Elimina una convocatoria específica.
     *
     * @param  string $call_uid El UID de la convocatoria.
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteEducationalPrograms(Request $request)
    {
        $uids = $request->input('uids');

        DB::transaction(function () use ($uids) {
            EducationalProgramsModel::destroy($uids);
            LogsController::createLog("Eliminación de programas educativos", 'Programas educativos', auth()->user()->uid);
        });

        return response()->json(['message' => 'Programas formativos eliminados correctamente']);
    }

    /**
     * Obtiene un programa formativo por uid
     */

    public function getEducationalProgram($educational_program_uid)
    {

        if (!$educational_program_uid) {
            return response()->json(['message' => env('ERROR_MESSAGE')], 400);
        }

        $educational_program = EducationalProgramsModel::where('uid', $educational_program_uid)->with('courses')->first();

        if (!$educational_program) {
            return response()->json(['message' => 'El programa formativo no existe'], 406);
        }

        return response()->json($educational_program, 200);
    }

    public function searchCoursesWithoutEducationalProgram($search)
    {
        $courses_query = CoursesModel::where('educational_program_uid', null);

        if ($search) {
            $courses_query->where(function ($query) use ($search) {
                $query->where('title', 'LIKE', "%{$search}%");
            });
        }

        $courses = $courses_query->limit(5)->get()->toArray();

        return response()->json($courses, 200);
    }
}
