<?php

namespace App\Http\Controllers\Webhooks;

use App\Models\BackendFileDownloadTokensModel;
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

    public function downloadFileToken(Request $request)
    {
        $token = $request->input('token');
        $backendFileDownloadToken = BackendFileDownloadTokensModel::where('token', $token)->first();

        if(!$backendFileDownloadToken) abort(406);

        $filePath = storage_path($backendFileDownloadToken->file);
        $fileName = basename($backendFileDownloadToken->file);

        $backendFileDownloadToken->delete();

        return response()->download($filePath, $fileName, [
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }

}
