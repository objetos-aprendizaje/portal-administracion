<?php

namespace App\Http\Controllers\Administration;

use App\Models\FooterPagesModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\GeneralOptionsModel;

class FooterPagesController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
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
        $footer_page_uid = $request->input('footer_page_uid');

        if (!$footer_page_uid) {
            $isNew = true;
            $footer_page = new FooterPagesModel();
            $footer_page->uid = generate_uuid();
        } else {
            $isNew = false;
            $footer_page = FooterPagesModel::where('uid', $footer_page_uid)->first();
        }

        $footer_page->name = $request->input('name');
        $footer_page->content = $request->input('content');
        $footer_page->save();

        return response()->json(['message' => $isNew ? 'Página de footer creada correctamente' : 'Página de footer actualizada correctamente']);
    }

    public function deleteFooterPages(Request $request)
    {
        $uids = $request->input('uids');

        FooterPagesModel::destroy($uids);

        return response()->json(['message' => 'Páginas de footer eliminadas correctamente']);
    }
}
