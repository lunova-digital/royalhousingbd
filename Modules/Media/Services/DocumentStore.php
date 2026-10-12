<?php

namespace Modules\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Stores uploaded PDF documents (brochures) on the public disk, served at /storage. */
class DocumentStore
{
    /**
     * @return array{path: string, size: int}
     */
    public static function storePdf(UploadedFile $file, string $field, string $dir): array
    {
        $isPdf = $file->getMimeType() === 'application/pdf'
            && str_starts_with((string) file_get_contents($file->getRealPath(), false, null, 0, 5), '%PDF-');
        if (! $isPdf) {
            throw ValidationException::withMessages([$field => 'Only PDF files are supported.']);
        }

        $disk = Storage::disk('public');
        $folder = 'uploads/'.trim($dir, '/').'/'.now()->format('Y/m');
        $name = time().Str::lower(Str::random(16)).'.pdf';
        $disk->putFileAs($folder, $file, $name);

        return ['path' => "{$folder}/{$name}", 'size' => (int) $file->getSize()];
    }

    /** Remove stored files, ignoring ones already gone. */
    public static function delete(?string ...$paths): void
    {
        ImageStore::delete(...$paths);
    }
}
