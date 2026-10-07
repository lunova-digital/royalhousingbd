<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Settings\Settings;

class SettingsController
{
    public function index(): JsonResponse
    {
        return response()->json(['settings' => Settings::all(), 'keys' => Settings::KEYS]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['settings' => ['required', 'array']]);
        Settings::save($data['settings']);

        return $this->index();
    }
}
