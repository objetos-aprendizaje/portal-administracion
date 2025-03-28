<?php


namespace App\Http\Controllers\Cataloging;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\CategoriesModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use Illuminate\Http\Request;

class CategoriesController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $canAccessCategories = $this->checkAccessUserCategories();

            if (!$canAccessCategories) {
                abort(403);
            }
            return $next($request);
        })->except('index');
    }

    public function index()
    {
        $canAccessCategories = $this->checkAccessUserCategories();

        if (!$canAccessCategories) {
            return view('access_not_allowed', [
                'title' => 'No es posible administrar las categorías',
                'description' => 'El administrador tiene bloqueado la administración de categorías a los gestores.'
            ]);
        }

        // Cargar las categorías principales
        $categoriesAnidated = $this->getCategoriesAnidatedWithCoursesCount();

        return view(
            'cataloging.categories.index',
            [
                "page_name" => "Categorías",
                "page_title" => "Categorías",
                "resources" => [
                    "resources/js/cataloging_module/categories.js"
                ],
                "categories_anidated" => $categoriesAnidated,
                "coloris" => true,
                "submenuselected" => "cataloging-categories",
            ]
        );
    }

    public function getCategory($categoryUid)
    {

        $category = CategoriesModel::with('parentCategory')->where('uid', $categoryUid)->first()->toArray();

        return response()->json($category, 200);
    }


    /**
     * Obtiene todas las categorías.
     */
    public function getCategories(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = CategoriesModel::with('parentCategory');

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

    /**
     * Guarda una categoría. Si recibe un uid, actualiza la categoría con ese uid.
     */
    public function saveCategory(Request $request)
    {

        $messages = [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'parent_category_uid.exists' => 'La categoría padre seleccionada no existe.',
            'color.required' => 'Debes especificar un color',
            'image_path.required' => 'Debes añadir una imagen',
            'description.max' => 'La descripción no puede tener más de 256 caracteres.'
        ];


        $rules = [
            'name' => 'required|max:255',
            'description' => 'nullable|max:256',
            'parent_category_uid' => 'nullable|exists:categories,uid',
            'color' => 'required',
            'image_path' => 'max:6144'
        ];

        if (!$request->input("category_uid")) {
            $rules['image_path'] = 'required|file|max:6144';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validator->errors()], 422);
        }

        $categoryUid = $request->get('category_uid');
        $name = $request->get('name');
        $description = $request->get('description');
        $color = $request->get('color');

        $parentCategoryUid = $request->get('parent_category_uid');

        if ($categoryUid) {
            $isNew = false;
            $category = CategoriesModel::find($categoryUid);
        } else {
            $isNew = true;
            $category = new CategoriesModel();
            $category->uid = generateUuid();
        }

        $category->name = $name;
        $category->description = $description;
        $category->parent_category_uid = $parentCategoryUid;
        $category->color = $color;

        $image = $request->file('image_path');

        if ($image) {
            $path = 'images/categories-images';
            $destinationPath = public_path($path);
            $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $image->getClientOriginalExtension();
            $timestamp = time();
            $filename = "{$originalName}-{$timestamp}.{$extension}";
            $image->move($destinationPath, $filename);
            $category->image_path = $path . "/" . $filename;
        }

        DB::transaction(function () use ($category) {
            $category->save();
            LogsController::createLog('Crear categoría: ' . $category->name, 'Categorías', auth()->user()->uid);
        });

        return response()->json(['message' => $isNew ? 'Categoría añadida correctamente' : 'Categoría modificada correctamente'], 200);
    }

    public function getListCategories(Request $request)
    {
        $search = $request->input("search");

        $categories = $this->getCategoriesAnidatedWithCoursesCount($search);

        // Asumiendo que 'renderCategories' es una función que ya tienes para generar el HTML
        $html = renderCategories($categories);

        return response()->json(['html' => $html]);
    }

    private function getCategoriesAnidatedWithCoursesCount($search = null) {
        $query = CategoriesModel::query()->whereNull('parent_category_uid');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', '%' . $search . '%')
                    ->orWhere('description', 'ILIKE', '%' . $search . '%');
            });
        }

        $categories = $query->get();

        $categories = $this->loadCategoriesWithCourses($categories);

        return $categories;
    }

        /**
     * Función recursiva para cargar las categorías con los cursos que contienen.
     */
    function loadCategoriesWithCourses($categories)
    {
        return $categories->map(function ($category) {
            // Contar los cursos en la categoría actual
            $category->courses_count = $category->courses()->count();

            // Cargar las subcategorías de forma recursiva
            if ($category->subcategories()->exists()) {
                $category->subcategories = $this->loadCategoriesWithCourses($category->subcategories);
            }

            return $category;
        });
    }

    public function deleteCategories(Request $request)
    {

        $uidsCategories = $request->input('uids');

        $categories = CategoriesModel::whereIn('uid', $uidsCategories)->get();
        DB::transaction(function () use ($categories) {
            foreach ($categories as $category) {
                $category->delete();
                LogsController::createLog('Eliminar categoría: ' . $category->name, 'Categorías', auth()->user()->uid);
            }
        });

        return response()->json(['message' => 'Categorías eliminadas correctamente'], 200);
    }

    /**
     * Si el usuario es sólo gestor, debemos comprobar si están habilitadas la administración de categorías
     * para gestores
     */
    private function checkAccessUserCategories()
    {
        $user = Auth::user();

        $rolesUser = $user->roles->pluck('code')->toArray();

        if (empty(array_diff($rolesUser, ['MANAGEMENT']))) {
            $managersCanManageCategories = GeneralOptionsModel::where('option_name', 'managers_can_manage_categories')->first()->option_value;

            if (!$managersCanManageCategories) {
                return false;
            }
        }

        return true;
    }
}
