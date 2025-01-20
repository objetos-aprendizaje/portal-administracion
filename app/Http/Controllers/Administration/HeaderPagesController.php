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

    public function getHeaderPages(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = HeaderPagesModel::query();

        $query = $query->with('parentPageName');

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->whereRaw("name ILIKE ?", ["%$search%"])
                    ->orWhere('content', 'ILIKE', "%$search%");
            });
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $headerPages = $query->paginate($size);

        return response()->json($headerPages, 200);
    }

    public function getHeaderPage(Request $request, $headerPageUid)
    {
        $headerPage = HeaderPagesModel::where('uid', $headerPageUid)->first();

        return response()->json($headerPage, 200);
    }

    public function saveHeaderPage(Request $request)
    {

        $messages = [
            'name.required' => 'El campo nombre es obligatorio',
            'order.required' => 'El campo orden es obligatorio',
            'order.numeric' => 'El campo orden debe ser numérico',
            'slug.regex' => 'El campo Slug solo puede contener letras minúsculas, números, guiones y guiones bajos',
            'slug.required' => 'El campo Slug es obligatorio',
            'content.required' => 'El campo contenido es obligatorio',
        ];

        $validatorRules = [
            'name' => 'required',
            'order' => 'required|numeric',
            'slug' => ['required', 'regex:/^[a-z0-9_-]+$/i', 'max:255'],
            'content' => 'required',
        ];

        $validator = Validator::make($request->all(), $validatorRules, $messages);


        if ($validator->fails()) {
            return response()->json(['message' => 'Hay campos incorrectos', 'errors' => $validator->errors()], 422);
        }


        $headerPageUid = $request->input('header_page_uid');
        $exist = false;

        if (!$headerPageUid) {
            $isNew = true;
            $headerPage = new HeaderPagesModel();
            $headerPage->uid = generateUuid();
            if (HeaderPagesModel::where('slug', $request->input('slug'))->first()){
                $exist = true;
            }
        } else {
            $isNew = false;
            $headerPage = HeaderPagesModel::where('uid', $headerPageUid)->first();
            if (HeaderPagesModel::where('slug', $request->input('slug'))->where('uid', '!=', $headerPageUid)->first()){
                $exist = true;
            }
        }

        if (FooterPagesModel::where('slug', $request->input('slug'))->first()){
            $exist = true;
        }

        if ($exist){
            throw new OperationFailedException("El slug intriducido ya existe", 406);
        }

        $headerPage->name = $request->input('name');
        $headerPage->content = $request->input('content');
        $headerPage->slug = $request->input('slug');
        $headerPage->order = $request->input('order');
        $headerPage->header_page_uid = $request->input('parent_page_uid');

        $headerPage->save();

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
