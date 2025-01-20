<?php

namespace App\Http\Controllers\Webhooks;

use App\Exceptions\OperationFailedException;
use App\Models\BackendFileDownloadTokensModel;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class FileController extends BaseController
{

    public function uploadFile(Request $request)
    {
        $file = $request->file('file');

        if(!$file) {
            abort(406);
        }

        $photoPath = saveFile($file, '/app/files', null, false);

        return response()->json(['file_path' => $photoPath]);

    }

    public function downloadFileToken(Request $request)
    {
        $token = $request->input('token');
        $backendFileDownloadToken = BackendFileDownloadTokensModel::where('token', $token)->first();

        if(!$backendFileDownloadToken) {
            abort(406);
        }

        $filePath = storage_path($backendFileDownloadToken->file);
        if (!file_exists($filePath)) {
            throw new OperationFailedException('File not found');
        }

        $fileName = basename($backendFileDownloadToken->file);

        $backendFileDownloadToken->delete();

        return response()->download($filePath, $fileName, [
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }

}
