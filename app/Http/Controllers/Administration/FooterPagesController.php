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

        $pages = FooterPagesModel::get();

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

    public function getFooterPages(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = FooterPagesModel::query();

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

        $footerPages = $query->paginate($size);

        return response()->json($footerPages, 200);
    }

    public function getFooterPage(Request $request, $footerPageUid)
    {
        $footerPage = FooterPagesModel::where('uid', $footerPageUid)->first();

        return response()->json($footerPage, 200);
    }

    public function saveFooterPage(Request $request)
    {

        $messages = [
            'name.required' => 'El campo nombre es obligatorio',
            'slug.required' => 'El campo slug es obligatorio',
            'content.required' => 'El campo contenido es obligatorio',
            'version.required_if' => 'El campo versión es obligatorio si se requiere aceptación',
            'slug.max' => 'El campo slug no puede tener más de 255 caracteres',
            'slug.regex' => 'El campo slug solo puede contener letras minúsculas, números, guiones y guiones bajos'
        ];

        $validatorRules = [
            'name' => ['required', 'max:255'],
            'slug' => ['required', 'regex:/^[a-z0-9_-]+$/i', 'max:255'],
            'content' => ['required'],
            'version' => 'required_if:acceptance_required,1'
        ];

        $validator = Validator::make($request->all(), $validatorRules, $messages);


        if ($validator->fails()) {
            return response()->json(['message' => 'Hay campos incorrectos', 'errors' => $validator->errors()], 422);
        }

        $footerPageUid = $request->input('footer_page_uid');
        $exist = false;

        if (!$footerPageUid) {
            $isNew = true;
            $footerPage = new FooterPagesModel();
            $footerPage->uid = generateUuid();
            if (FooterPagesModel::where('slug', $request->input('slug'))->first()){
                $exist = true;
            }
        } else {
            $isNew = false;
            $footerPage = FooterPagesModel::where('uid', $footerPageUid)->first();

            if(FooterPagesModel::where('slug', $request->input('slug'))->where('uid', '!=', $footerPageUid)->first()){
                $exist = true;
            }
        }

        if (HeaderPagesModel::where('slug', $request->input('slug'))->first()){
            $exist = true;
        }

        if ($exist){
            throw new OperationFailedException("El slug intriducido ya existe", 406);
        }


        $footerPage->name = $request->input('name');
        $footerPage->content = $request->input('content');
        $footerPage->slug = $request->input('slug');
        $footerPage->version = $request->input('version');
        $footerPage->acceptance_required = $request->input('acceptance_required');
        $footerPage->save();

        return response()->json(['message' => $isNew ? 'Página de footer creada correctamente' : 'Página de footer actualizada correctamente']);
    }

    public function deleteFooterPages(Request $request)
    {
        $uids = $request->input('uids');

        FooterPagesModel::destroy($uids);

        return response()->json(['message' => 'Páginas de footer eliminadas correctamente']);
    }

}
