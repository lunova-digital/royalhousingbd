<?php

namespace Modules\Service\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Service\Models\Service;

/** Site → Other services: the whole list is saved at once, in order (like the ticker and videos). */
class ServiceController
{
    public function index(): JsonResponse
    {
        return response()->json(['items' => Service::query()->ordered()->get()]);
    }

    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['present', 'array', 'max:30'],
            'items.*.slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]*$/'],
            'items.*.title' => ['required', 'string', 'max:120'],
            'items.*.title_bn' => ['nullable', 'string', 'max:120'],
            'items.*.summary' => ['nullable', 'string', 'max:300'],
            'items.*.summary_bn' => ['nullable', 'string', 'max:300'],
            'items.*.description' => ['nullable', 'string', 'max:5000'],
            'items.*.description_bn' => ['nullable', 'string', 'max:5000'],
            'items.*.benefits' => ['nullable', 'array', 'max:8'],
            'items.*.benefits.*' => ['nullable', 'string', 'max:160'],
            'items.*.benefits_bn' => ['nullable', 'array', 'max:8'],
            'items.*.benefits_bn.*' => ['nullable', 'string', 'max:160'],
            'items.*.image' => ['nullable', 'string', 'max:255'],
            'items.*.gallery' => ['nullable', 'array', 'max:8'],
            'items.*.gallery.*' => ['nullable', 'string', 'max:255'],
            'items.*.active' => ['boolean'],
        ], ['items.*.title.required' => 'Every service needs a title.']);

        DB::transaction(function () use ($data) {
            $kept = [];
            foreach ($data['items'] as $i => $item) {
                $slug = $this->slug($item['slug'] ?? '', $item['title'], $kept);
                $kept[] = $slug;
                $clean = fn (?array $list) => array_values(array_filter(array_map(fn ($v) => trim((string) $v), $list ?? []), fn ($v) => $v !== ''));
                Service::query()->updateOrCreate(['slug' => $slug], [
                    'title' => trim($item['title']),
                    'title_bn' => trim((string) ($item['title_bn'] ?? '')),
                    'summary' => trim((string) ($item['summary'] ?? '')),
                    'summary_bn' => trim((string) ($item['summary_bn'] ?? '')),
                    'description' => trim((string) ($item['description'] ?? '')),
                    'description_bn' => trim((string) ($item['description_bn'] ?? '')),
                    'benefits' => $clean($item['benefits'] ?? []),
                    'benefits_bn' => $clean($item['benefits_bn'] ?? []),
                    'image' => trim((string) ($item['image'] ?? '')),
                    'gallery' => $clean($item['gallery'] ?? []),
                    'active' => (bool) ($item['active'] ?? true),
                    'sort_order' => $i,
                ]);
            }
            Service::query()->whereNotIn('slug', $kept)->delete();
        });

        return $this->index();
    }

    /** A URL-safe, unique slug: the one given, else made from the title. */
    private function slug(string $given, string $title, array $taken): string
    {
        $base = $given !== '' ? $given : (Str::slug($title) ?: 'service');
        $slug = $base;
        for ($n = 2; in_array($slug, $taken, true); $n++) {
            $slug = $base.'-'.$n;
        }

        return $slug;
    }
}
