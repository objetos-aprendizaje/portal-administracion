<?php

namespace App\Http\Controllers\Administration;

use App\Exceptions\OperationFailedException;
use App\Models\FooterPagesModel;
use App\Models\HeaderPagesModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\GeneralOptionsModel;
use App\Rules\ValidSlugRule;
use Illuminate\Support\Facades\Validator;

class HeaderPagesController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $pages = HeaderPagesModel::whereNull('header_page_uid')->with('parentPage')->get();

        return view(
            'administration.header_pages.index',
            [
                "page_name" => "Páginas de header",
                "page_title" => "Páginas de header",
                "resources" => [
                    "resources/js/administration_module/header_pages.js",
                ],
                "tinymce" => true,
                "tabulator" => true,
                "submenuselected" => "header-pages",
                "pages" => $pages,
            ]
        );
    }

    public function saveHeaderPages(Request $request)
    {
        $updateData = [
            'legal_advice' => $request->input('legalAdvice'),
        ];

        foreach ($updateData as $key => $value) {
            GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
        }

        return response()->json(['message' => 'Textos guardados correctamente']);
    }

    public function getHeaderPages(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = HeaderPagesModel::query();

        $query = $query->with('parentPageName');

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->whereRaw("name like ?", ["%$search%"])
                    ->orWhere('content', 'like', "%$search%");
            });
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $header_pages = $query->paginate($size);

        return response()->json($header_pages, 200);
    }

    public function getHeaderPage(Request $request, $header_page_uid)
    {
        $header_page = HeaderPagesModel::where('uid', $header_page_uid)->first();

        return response()->json($header_page, 200);
    }

    public function saveHeaderPage(Request $request)
    {

        $exist = false;
        if (HeaderPagesModel::where('slug', $request->input('slug'))->first()){
            $exist = true;
        }
        if (FooterPagesModel::where('slug', $request->input('slug'))->first()){
            $exist = true;
        }

        if ($exist){
            throw new OperationFailedException("El slug intriducido ya existe", 406);
        }

        $messages = [
            'order.numeric' => 'El campo Orden debe ser numérico.',
            'slug.regex' => 'El campo Slug solo puede contener letras minúsculas, números, guiones y guiones bajos.'
        ];

        $validator_rules = [
            'order' => 'required|numeric',
            'slug' => ['required', 'regex:/^[a-z0-9_-]+$/i', 'max:255'],
        ];

        $validator = Validator::make($request->all(), $validator_rules, $messages);


        if ($validator->fails()) {
            return response()->json(['message' => 'Hay campos incorrectos', 'errors' => $validator->errors()], 422);
        }


        $header_page_uid = $request->input('header_page_uid');

        if (!$header_page_uid) {
            $isNew = true;
            $header_page = new HeaderPagesModel();
            $header_page->uid = generate_uuid();
        } else {
            $isNew = false;
            $header_page = HeaderPagesModel::where('uid', $header_page_uid)->first();
        }

        $header_page->name = $request->input('name');
        $header_page->content = $request->input('content');
        $header_page->slug = $request->input('slug');
        $header_page->order = $request->input('order');
        $header_page->header_page_uid = $request->input('parent_page_uid');

        $header_page->save();

        return response()->json(['message' => $isNew ? 'Página de header creada correctamente' : 'Página de header actualizada correctamente']);
    }

    public function deleteHeaderPages(Request $request)
    {
        $uids = $request->input('uids');

        HeaderPagesModel::destroy($uids);

        return response()->json(['message' => 'Páginas de header eliminadas correctamente']);
    }
    public function getHeaderPagesSelect(){

        $pages = HeaderPagesModel::whereNull('header_page_uid')->with('parentPage')->get();

        return response()->json($pages, 200);

    }
}
