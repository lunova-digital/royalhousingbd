<?php

namespace Modules\Account\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Account\Models\Member;

/** Profile pictures: cropped to a centred square, 256px, WebP, on the public disk. */
final class Avatar
{
    public const SIZE = 256;

    /** @return string the stored path relative to the public disk, e.g. avatars/12-ab34cd.webp */
    public static function store(UploadedFile $file, Member $member): string
    {
        $name = 'avatars/'.$member->id.'-'.Str::lower(Str::random(8)).'.webp';
        $disk = Storage::disk('public');
        $disk->makeDirectory('avatars');
        $target = $disk->path($name);
        $size = self::SIZE;

        // -auto-orient respects phone photos; the crop is the largest centred square (the browser's cropper already framed it).
        $result = Process::run(['magick', $file->getRealPath().'[0]', '-auto-orient', '-gravity', 'center', '-crop', '1:1+0+0', '+repage', '-resize', "{$size}x{$size}", '-strip', '-quality', '84', $target]);
        if (! $result->successful() || ! is_file($target)) {
            // No ImageMagick on this server: keep the upload as it came (the page crops it with CSS).
            $name = 'avatars/'.$member->id.'-'.Str::lower(Str::random(8)).'.'.($file->guessExtension() ?: 'jpg');
            $disk->putFileAs('avatars', $file, basename($name));
        }

        return $name;
    }

    public static function delete(?string $path): void
    {
        if ($path && str_starts_with($path, 'avatars/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
