<?php

namespace Modules\Listing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Listing\Models\Location;

/** Locations CRUD for the admin, under /api/admin/locations. */
class LocationController
{
    public function index(): JsonResponse
    {
        return response()->json(['locations' => $this->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', Rule::unique('locations', 'name')]]);
        $name = trim($data['name']);
        $location = Location::query()->create(['name' => $name, 'slug' => $this->uniqueSlug($name)]);

        return response()->json(['location' => $location->loadCount('listings'), 'locations' => $this->all()], 201);
    }

    /** Renaming also updates the name stored on every listing in it. */
    public function update(Request $request, Location $location): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', Rule::unique('locations', 'name')->ignore($location->id)]]);
        $name = trim($data['name']);
        $location->update(['name' => $name, 'slug' => $this->uniqueSlug($name, $location->id)]);

        return response()->json(['location' => $location->loadCount('listings'), 'locations' => $this->all()]);
    }

    /** A location with listings cannot be removed: every listing must have one. */
    public function destroy(Location $location): JsonResponse
    {
        if ($location->listings()->exists()) {
            return response()->json(['message' => 'Move or delete its listings first.'], 422);
        }
        $location->delete();

        return response()->json(['locations' => $this->all()]);
    }

    private function all(): array
    {
        return Location::query()->ordered()->withCount('listings')->get()->all();
    }

    private function uniqueSlug(string $name, ?int $selfId = null): string
    {
        $base = Str::slug($name) ?: 'location';
        $slug = $base;
        for ($i = 2; ; $i++) {
            $taken = Location::query()->where('slug', $slug)->when($selfId, fn ($q) => $q->where('id', '!=', $selfId))->exists();
            if (! $taken) {
                return $slug;
            }
            $slug = "{$base}-{$i}";
        }
    }
}
