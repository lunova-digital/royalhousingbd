<?php

namespace Modules\Masterplan\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Masterplan\Http\Requests\MasterplanRequest;
use Modules\Masterplan\Http\Requests\PlotsRequest;
use Modules\Masterplan\Models\Masterplan;
use Modules\Masterplan\Services\MasterplanService;

/** Map management for the admin (FR-ADM-004, FR-MAP-009), under /api/admin. */
class MasterplanController
{
    public function __construct(private MasterplanService $masterplans) {}

    public function index(): JsonResponse
    {
        return response()->json(['masterplans' => Masterplan::query()->with('plots')->orderBy('id')->get()]);
    }

    public function show(Masterplan $masterplan): JsonResponse
    {
        return response()->json(['masterplan' => $masterplan->load('plots')]);
    }

    /** Multipart: the SVG layout as `layout` plus name, description, published. */
    public function store(MasterplanRequest $request): JsonResponse
    {
        return response()->json(['masterplan' => $this->masterplans->create($request->attributesForModel(), $request->layoutSvg())], 201);
    }

    public function update(MasterplanRequest $request, Masterplan $masterplan): JsonResponse
    {
        return response()->json(['masterplan' => $this->masterplans->update($masterplan, $request->attributesForModel())]);
    }

    public function destroy(Masterplan $masterplan): JsonResponse
    {
        $this->masterplans->delete($masterplan);

        return response()->json(['message' => 'Deleted.']);
    }

    public function setLayout(Request $request, Masterplan $masterplan): JsonResponse
    {
        $request->validate(['layout' => ['required', 'file', 'max:10240']]);
        $svg = (string) file_get_contents($request->file('layout')->getRealPath());

        return response()->json(['masterplan' => $this->masterplans->setLayout($masterplan, $svg)]);
    }

    public function savePlots(PlotsRequest $request, Masterplan $masterplan): JsonResponse
    {
        return response()->json(['masterplan' => $this->masterplans->savePlots($masterplan, $request->validated('plots'))]);
    }
}
