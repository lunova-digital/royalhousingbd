<?php

namespace Modules\Media\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Media\Services\ImageStore;

/** A generic image upload for hero, brand and SEO images. */
class UploadController
{
    public function store(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:20480']]);

        return response()->json(ImageStore::store($request->file('file'), 'file', 'site'), 201);
    }
}
