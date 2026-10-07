<?php

namespace Modules\Content\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Content\Models\TickerItem;
use Modules\Content\Models\Video;
use Modules\Site\Support\YouTube;

/** The homepage's admin-managed lists: ticker and videos. Each save replaces the whole list, in order. */
class ContentController
{
    public function ticker(): JsonResponse
    {
        return response()->json(['items' => TickerItem::query()->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function saveTicker(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['present', 'array'],
            'items.*.text' => ['required', 'string', 'max:300'],
            'items.*.link' => ['nullable', 'string', 'max:255'],
            'items.*.active' => ['boolean'],
        ], ['items.*.text.required' => 'Every ticker item needs text.']);

        DB::transaction(function () use ($data) {
            TickerItem::query()->delete();
            foreach ($data['items'] as $i => $item) {
                TickerItem::query()->create([
                    'text' => trim($item['text']),
                    'link' => trim((string) ($item['link'] ?? '')),
                    'active' => (bool) ($item['active'] ?? true),
                    'sort_order' => $i,
                ]);
            }
        });

        return $this->ticker();
    }

    public function videos(): JsonResponse
    {
        return response()->json(['items' => Video::query()->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function saveVideos(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['present', 'array'],
            'items.*.title' => ['nullable', 'string', 'max:200'],
            'items.*.url' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (YouTube::id($value) === null) {
                    $fail('Every video needs a YouTube link.');
                }
            }],
        ]);

        DB::transaction(function () use ($data) {
            Video::query()->delete();
            foreach ($data['items'] as $i => $item) {
                Video::query()->create([
                    'title' => trim((string) ($item['title'] ?? '')),
                    'url' => trim($item['url']),
                    'sort_order' => $i,
                ]);
            }
        });

        return $this->videos();
    }
}
