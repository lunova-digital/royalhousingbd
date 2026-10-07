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
            // Creating sends the SVG layout along with the info (multipart).
            'layout' => $this->isMethod('post') ? ['required', 'file', 'max:10240'] : ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'layout.required' => 'Upload the SVG layout first.',
            'layout.max' => 'The layout must be smaller than 10 MB.',
        ];
    }

    public function attributesForModel(): array
    {
        $data = $this->validated();

        return [
            'name' => trim($data['name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'published' => filter_var($data['published'] ?? false, FILTER_VALIDATE_BOOL),
        ];
    }

    public function layoutSvg(): string
    {
        return (string) file_get_contents($this->file('layout')->getRealPath());
    }
}
