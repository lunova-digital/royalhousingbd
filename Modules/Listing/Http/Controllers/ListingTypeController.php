<?php

namespace Modules\Listing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Listing\Models\ListingType;

/** Listing types CRUD for the admin, under /api/admin/listing-types. The slug is set on create and never changes. */
class ListingTypeController
{
    public function index(): JsonResponse
    {
        return response()->json(['types' => $this->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60']]);
        $name = trim($data['name']);
        $slug = Str::slug($name);
        if ($slug === '' || ListingType::query()->where('slug', $slug)->exists() || in_array($slug, ListingType::reservedSlugs(), true)) {
            throw ValidationException::withMessages(['name' => "\"{$name}\" cannot be used as a listing type: its address /{$slug} is taken."]);
        }
        $type = ListingType::query()->create(['name' => $name, 'slug' => $slug, 'sort_order' => (int) ListingType::query()->max('sort_order') + 1]);

        return response()->json(['type' => $type->loadCount('listings'), 'types' => $this->all()], 201);
    }

    public function update(Request $request, ListingType $listingType): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60', Rule::unique('listing_types', 'name')->ignore($listingType->id)]]);
        $listingType->update(['name' => trim($data['name'])]);

        return response()->json(['type' => $listingType->loadCount('listings'), 'types' => $this->all()]);
    }

    public function destroy(ListingType $listingType): JsonResponse
    {
        if ($listingType->listings()->exists()) {
            throw ValidationException::withMessages(['type' => 'Move or delete its listings first.']);
        }
        $listingType->delete();

        return response()->json(['types' => $this->all()]);
    }

    /** Put the types in the given order (ids), any not listed after them; it is the order of the public navigation. */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'exists:listing_types,id']]);
        $rest = ListingType::query()->ordered()->whereNotIn('id', $data['ids'])->pluck('id')->all();
        DB::transaction(function () use ($data, $rest) {
            foreach (array_values([...$data['ids'], ...$rest]) as $i => $id) {
                ListingType::query()->where('id', $id)->update(['sort_order' => $i]);
            }
        });

        return response()->json(['types' => $this->all()]);
    }

    private function all(): array
    {
        return ListingType::query()->ordered()->withCount('listings')->get()->all();
    }
}
