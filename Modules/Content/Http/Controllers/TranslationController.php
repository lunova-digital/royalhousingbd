<?php

namespace Modules\Content\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\Services\TranslationSources;

/** Site → Bangla content: the Bangla version of everything the admin types in English. */
class TranslationController
{
    public function __construct(private TranslationSources $sources) {}

    public function index(): JsonResponse
    {
        return response()->json(['sections' => $this->sources->catalogue()]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'max:2000'],
            'items.*.group' => ['required', 'string', 'in:setting,string,listing_type,location,feature,listing,masterplan,ticker,video'],
            'items.*.key' => ['required', 'string', 'max:191'],
            'items.*.field' => ['nullable', 'string', 'max:32'],
            'items.*.value' => ['nullable', 'string', 'max:20000'],
        ]);
        $this->sources->save($data['items']);

        return $this->index();
    }
}
