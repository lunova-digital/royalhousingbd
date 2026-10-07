<?php

namespace Modules\Listing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Listing\Http\Requests\ListingRequest;
use Modules\Listing\Models\Listing;
use Modules\Listing\Models\ListingImage;
use Modules\Listing\Services\ListingService;

/** Listing CRUD for the admin (FR-ADM-003), under /api/admin. */
class ListingController
{
    public function __construct(private ListingService $listings) {}

    public function index(Request $request): JsonResponse
    {
        $page = $this->listings->search(
            ['type' => $request->query('type'), 'q' => $request->query('q')],
            (int) $request->query('per_page', 20),
            (int) $request->query('page', 1),
        );

        return response()->json(['listings' => $page->items(), 'total' => $page->total()]);
    }

    public function show(Listing $listing): JsonResponse
    {
        return response()->json(['listing' => $listing->load('images')]);
    }

    public function store(ListingRequest $request): JsonResponse
    {
        return response()->json(['listing' => $this->listings->create($request->attributesForModel())], 201);
    }

    public function update(ListingRequest $request, Listing $listing): JsonResponse
    {
        return response()->json(['listing' => $this->listings->update($listing, $request->attributesForModel())]);
    }

    public function destroy(Listing $listing): JsonResponse
    {
        $this->listings->delete($listing);

        return response()->json(['message' => 'Deleted.']);
    }

    public function addImages(Request $request, Listing $listing): JsonResponse
    {
        $request->validate(['files' => ['required', 'array', 'min:1'], 'files.*' => ['file', 'max:20480']]);

        return response()->json(['images' => $this->listings->addImages($listing, $request->file('files'))], 201);
    }

    public function reorderImages(Request $request, Listing $listing): JsonResponse
    {
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);
        $this->listings->reorderImages($listing, $data['ids']);

        return $this->show($listing->refresh());
    }

    public function deleteImage(Listing $listing, ListingImage $image): JsonResponse
    {
        abort_unless($image->listing_id === $listing->id, 404);
        $this->listings->deleteImage($image);

        return response()->json(['message' => 'Deleted.']);
    }

    public function featured(): JsonResponse
    {
        return response()->json(['listings' => $this->listings->featured(publishedOnly: false)]);
    }

    public function setFeatured(Request $request): JsonResponse
    {
        $data = $request->validate(['ids' => ['array'], 'ids.*' => ['integer']]);
        $this->listings->setFeatured($data['ids'] ?? []);

        return $this->featured();
    }
}
