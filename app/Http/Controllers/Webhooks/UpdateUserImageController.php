<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class UpdateUserImageController extends BaseController
{
    public function index(Request $request)
    {
        $image = $request->file('file');

        $photoPath = $this->saveUserImage($image);

        return response()->json(['photo_path' => $photoPath]);
    }

    private function saveUserImage($image) {

        $path = 'images/users-images';
        $destinationPath = public_path($path);
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $image->getClientOriginalExtension();
        $timestamp = time();

        $filename = "{$originalName}-{$timestamp}.{$extension}";

        $image->move($destinationPath, $filename);

        return $path . "/" . $filename;
    }


}
