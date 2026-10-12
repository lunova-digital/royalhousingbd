<?php

namespace Modules\Content\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\Services\SiteTexts;

/** Site → Site text: every public string with the admin's replacement per language. */
class TextController
{
    public function __construct(private SiteTexts $texts) {}

    public function index(): JsonResponse
    {
        return response()->json(['groups' => $this->texts->catalogue()]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'texts' => ['required', 'array'],
            'texts.*' => ['array'],
            'texts.*.en' => ['nullable', 'string', 'max:5000'],
            'texts.*.bn' => ['nullable', 'string', 'max:5000'],
        ]);
        $this->texts->save($data['texts']);

        return $this->index();
    }
}
