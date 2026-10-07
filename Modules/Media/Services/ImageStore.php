<?php

namespace Modules\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores uploaded images on the public disk (served at /storage) and
 * optimises them on the way in (NFR-002) with ImageMagick: re-encoded,
 * capped in width, with an optional thumbnail for grids. Without ImageMagick
 * the original is stored as-is.
 */
class ImageStore
{
    public const MAX_WIDTH = 1920;

    /** Masterplan images keep more detail so plots stay legible when zoomed. */
    public const MAX_PLAN_WIDTH = 4096;

    public const THUMB_WIDTH = 640;

    private const MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'png',
        'image/webp' => 'jpg',
    ];

    /**
     * @return array{path: string, thumb_path: string, width: int, height: int}
     */
    public static function store(UploadedFile $file, string $field, string $dir, int $maxWidth = self::MAX_WIDTH, bool $thumb = false): array
    {
        $ext = self::MIMES[$file->getMimeType()] ?? null;
        if ($ext === null || ! @getimagesize($file->getRealPath())) {
            throw ValidationException::withMessages([$field => 'Only JPEG, PNG, GIF or WebP images are supported.']);
        }

        $disk = Storage::disk('public');
        $folder = 'uploads/'.trim($dir, '/').'/'.now()->format('Y/m');
        $disk->makeDirectory($folder);
        $name = time().Str::lower(Str::random(16));

        $path = "{$folder}/{$name}.{$ext}";
        self::write($file->getRealPath(), $disk->path($path), $maxWidth);
        [$width, $height] = getimagesize($disk->path($path)) ?: [0, 0];

        $thumbPath = '';
        if ($thumb) {
            $thumbPath = "{$folder}/{$name}_thumb.{$ext}";
            self::write($disk->path($path), $disk->path($thumbPath), self::THUMB_WIDTH);
        }

        return ['path' => $path, 'thumb_path' => $thumbPath, 'width' => $width, 'height' => $height];
    }

    /** Remove stored files, ignoring ones already gone. */
    public static function delete(?string ...$paths): void
    {
        $disk = Storage::disk('public');
        foreach ($paths as $path) {
            if ($path && ! str_contains($path, '..')) {
                $disk->delete($path);
            }
        }
    }

    private static function write(string $source, string $target, int $maxWidth): void
    {
        if (self::hasMagick()) {
            $result = Process::run(['magick', $source, '-auto-orient', '-strip', '-resize', "{$maxWidth}>", '-quality', '82', $target]);
            if ($result->successful()) {
                return;
            }
        }
        copy($source, $target);
    }

    private static ?bool $magick = null;

    private static function hasMagick(): bool
    {
        return self::$magick ??= Process::run(['magick', '-version'])->successful();
    }
}
