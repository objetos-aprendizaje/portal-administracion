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

class FooterPagesController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $pages = FooterPagesModel::whereNull('footer_page_uid')->with('parentPage')->get();

        return view(
            'administration.footer_pages.index',
            [
                "page_name" => "Páginas de footer",
                "page_title" => "Páginas de footer",
                "resources" => [
                    "resources/js/administration_module/footer_pages.js",
                ],
                "tinymce" => true,
                "tabulator" => true,
                "submenuselected" => "footer-pages",
                "pages" => $pages,
            ]
        );
    }

    public function saveFooterPages(Request $request)
    {
        $updateData = [
            'legal_advice' => $request->input('legalAdvice'),
        ];

        foreach ($updateData as $key => $value) {
            GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
        }

        return response()->json(['message' => 'Textos guardados correctamente']);
    }

    public function getFooterPages(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = FooterPagesModel::query();

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

        $footer_pages = $query->paginate($size);

        return response()->json($footer_pages, 200);
    }

    public function getFooterPage(Request $request, $footer_page_uid)
    {
        $footer_page = FooterPagesModel::where('uid', $footer_page_uid)->first();

        return response()->json($footer_page, 200);
    }

    public function saveFooterPage(Request $request)
    {

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

        $footer_page_uid = $request->input('footer_page_uid');
        $exist = false;

        if (!$footer_page_uid) {
            $isNew = true;
            $footer_page = new FooterPagesModel();
            $footer_page->uid = generate_uuid();
            if (FooterPagesModel::where('slug', $request->input('slug'))->first()){
                $exist = true;
            }
        } else {
            $isNew = false;
            $footer_page = FooterPagesModel::where('uid', $footer_page_uid)->first();

            if(FooterPagesModel::where('slug', $request->input('slug'))->where('uid', '!=', $footer_page_uid)->first()){
                $exist = true;
            }
        }

        if (HeaderPagesModel::where('slug', $request->input('slug'))->first()){
            $exist = true;
        }

        if ($exist){
            throw new OperationFailedException("El slug intriducido ya existe", 406);
        }


        $footer_page->name = $request->input('name');
        $footer_page->content = $request->input('content');
        $footer_page->slug = $request->input('slug');
        $footer_page->order = $request->input('order');
        $footer_page->footer_page_uid = $request->input('parent_page_uid');
        $footer_page->version = $request->input('version');
        $footer_page->acceptance_required = $request->input('acceptance_required');
        $footer_page->save();

        return response()->json(['message' => $isNew ? 'Página de footer creada correctamente' : 'Página de footer actualizada correctamente']);
    }

    public function deleteFooterPages(Request $request)
    {
        $uids = $request->input('uids');

        FooterPagesModel::destroy($uids);

        return response()->json(['message' => 'Páginas de footer eliminadas correctamente']);
    }
    public function getFooterPagesSelect(){

        $pages = FooterPagesModel::whereNull('footer_page_uid')->with('parentPage')->get();

        return response()->json($pages, 200);

    }
}
