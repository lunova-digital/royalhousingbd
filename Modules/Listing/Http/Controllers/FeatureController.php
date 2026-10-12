<?php

namespace Modules\Listing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Listing\Models\Feature;

/** Features (amenities) CRUD for the admin, under /api/admin/features. */
class FeatureController
{
    public function index(): JsonResponse
    {
        return response()->json(['features' => $this->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', Rule::unique('features', 'name')]]);
        $name = trim($data['name']);
        $feature = Feature::query()->create(['name' => $name, 'slug' => $this->uniqueSlug($name)]);

        return response()->json(['feature' => $feature->loadCount('listings'), 'features' => $this->all()], 201);
    }

    public function update(Request $request, Feature $feature): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', Rule::unique('features', 'name')->ignore($feature->id)]]);
        $name = trim($data['name']);
        $feature->update(['name' => $name, 'slug' => $this->uniqueSlug($name, $feature->id)]);

        return response()->json(['feature' => $feature->loadCount('listings'), 'features' => $this->all()]);
    }

    /** Removes the feature from every listing that has it. */
    public function destroy(Feature $feature): JsonResponse
    {
        $feature->delete();

        return response()->json(['features' => $this->all()]);
    }

    private function all(): array
    {
        return Feature::query()->ordered()->withCount('listings')->get()->all();
    }

    private function uniqueSlug(string $name, ?int $selfId = null): string
    {
        $base = Str::slug($name) ?: 'feature';
        $slug = $base;
        for ($i = 2; ; $i++) {
            $taken = Feature::query()->where('slug', $slug)->when($selfId, fn ($q) => $q->where('id', '!=', $selfId))->exists();
            if (! $taken) {
                return $slug;
            }
            $slug = "{$base}-{$i}";
        }
    }
}
