<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class FileController extends BaseController
{

    public function uploadFile(Request $request)
    {
        $file = $request->file('file');

        if(!$file) abort(406);

        $photo_path = saveFile($file, '/app/files', null, false);

        return response()->json(['file_path' => $photo_path]);

    }

    public function downloadFile(Request $request)
    {

        $file_path = $request->input('file_path');

        if(!$file_path) abort(406);

        return response()->download(storage_path($file_path));

    }

}
