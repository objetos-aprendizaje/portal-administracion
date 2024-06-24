<?php

namespace App\Http\Controllers\Administration;

use App\Models\HeaderPagesModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\GeneralOptionsModel;

class HeaderPagesController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
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
        $header_page->save();

        return response()->json(['message' => $isNew ? 'Página de header creada correctamente' : 'Página de header actualizada correctamente']);
    }

    public function deleteHeaderPages(Request $request)
    {
        $uids = $request->input('uids');

        HeaderPagesModel::destroy($uids);

        return response()->json(['message' => 'Páginas de header eliminadas correctamente']);
    }
}
