<?php

namespace Modules\Masterplan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterplanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'published' => ['boolean'],
        ];
    }

    public function attributesForModel(): array
    {
        $data = $this->validated();

        return [
            'name' => trim($data['name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'published' => (bool) ($data['published'] ?? false),
        ];
    }
}
