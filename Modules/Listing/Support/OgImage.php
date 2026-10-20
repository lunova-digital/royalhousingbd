<?php

namespace Modules\Listing\Support;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Modules\Listing\Models\Listing;
use Modules\Site\Site;

/**
 * The 1200×630 link preview of a listing (Open Graph / Twitter): its first photo cropped to size, or, when it has none, a
 * branded card (logo, title and price on the theme background). Needs ImageMagick; cached on the public disk and rebuilt
 * when the listing changes. Kept under ~250 KB so WhatsApp (which skips images over 300 KB) shows it.
 */
final class OgImage
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    /** A short token that changes whenever the picture would: used in the file name and the ?v= of the URL. */
    public static function version(Listing $listing): string
    {
        $cover = $listing->cover();

        return substr(md5(implode('|', [$listing->getRawOriginal('title'), $listing->getRawOriginal('price'), $listing->getRawOriginal('location'), $listing->call_for_price, $cover?->path, $listing->updated_at?->timestamp])), 0, 10);
    }

    public static function url(Listing $listing): string
    {
        return Site::baseUrl().'/og/'.$listing->type.'/'.$listing->slug.'.jpg?v='.self::version($listing);
    }

    /** The absolute path of the (possibly just generated) JPEG, or null when ImageMagick is unavailable or failed. */
    public static function path(Listing $listing): ?string
    {
        $disk = Storage::disk('public');
        $name = 'og/'.$listing->id.'-'.self::version($listing).'.jpg';
        if ($disk->exists($name)) {
            return $disk->path($name);
        }
        $disk->makeDirectory('og');
        foreach ($disk->files('og') as $old) { // drop stale versions of this listing
            if (str_starts_with(basename($old), $listing->id.'-')) {
                $disk->delete($old);
            }
        }
        $target = $disk->path($name);
        $cover = $listing->cover();
        $source = $cover ? $disk->path($cover->path) : null;

        $ok = $source && is_file($source) ? self::fromPhoto($source, $target) : self::brandedCard($listing, $target);

        return $ok && is_file($target) ? $target : null;
    }

    private static function fromPhoto(string $source, string $target): bool
    {
        foreach ([82, 74, 66] as $quality) {
            $r = Process::run(['magick', $source.'[0]', '-auto-orient', '-resize', self::WIDTH.'x'.self::HEIGHT.'^', '-gravity', 'center', '-extent', self::WIDTH.'x'.self::HEIGHT, '-strip', '-interlace', 'JPEG', '-sampling-factor', '4:2:0', '-quality', (string) $quality, $target]);
            if (! $r->successful()) {
                return false;
            }
            if (filesize($target) < 250_000) {
                break;
            }
        }

        return true;
    }

    /** Logo + title + price (+ place) on the deep-emerald brand background, with a champagne rule. */
    private static function brandedCard(Listing $listing, string $target): bool
    {
        $tmp = storage_path('framework/og');
        @mkdir($tmp, 0775, true);
        $conf = $tmp.'/fonts.conf';
        file_put_contents($conf, '<?xml version="1.0"?><fontconfig><include ignore_missing="yes">/etc/fonts/fonts.conf</include><dir>'.base_path('resources/og/fonts').'</dir><cachedir>'.$tmp.'/cache</cachedir></fontconfig>');
        $env = ['FONTCONFIG_FILE' => $conf];

        $esc = fn (string $s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $title = $esc(mb_strimwidth((string) $listing->getRawOriginal('title'), 0, 90, '…'));
        $price = $listing->call_for_price || trim((string) $listing->getRawOriginal('price')) === '' ? 'Price on request' : (string) $listing->getRawOriginal('price');
        $meta = $esc(trim($listing->getRawOriginal('location').'  ·  '.$listing->typeLabel(), ' ·'));
        $serif = preg_match('/\p{Bengali}/u', $title) ? 'Noto Serif Bengali SemiBold' : 'Cormorant Garamond Light SemiBold';

        $make = function (string $name, string $markup, int $width) use ($tmp, $env) {
            $out = "{$tmp}/{$name}.png";
            $r = Process::env($env)->run(['magick', '-background', 'none', '-size', $width.'x', 'pango:'.$markup, $out]);

            return $r->successful() ? $out : null;
        };
        $titleImg = $make('title', '<span foreground="#FFFFFF" font_family="'.$serif.'" size="75776">'.$title.'</span>', 980);
        $priceImg = $make('price', '<span foreground="#C9A96E" font_family="Manrope ExtraLight SemiBold" size="40960">'.$esc($price).'</span>', 980);
        $metaImg = $make('meta', '<span foreground="#CFE0D8" font_family="Manrope ExtraLight SemiBold" size="26624">'.$meta.'</span>', 980);
        $brandImg = $make('brand', '<span foreground="#FFFFFF" font_family="Manrope ExtraLight SemiBold" size="28672">ROYAL HOUSING</span> <span foreground="#C9A96E" font_family="Manrope ExtraLight SemiBold" size="20480">BD</span>', 600);
        if (! $titleImg || ! $priceImg) {
            return false;
        }

        $args = ['magick', '-size', self::WIDTH.'x'.self::HEIGHT, 'gradient:#15503F-#0A2D23', '(', base_path('resources/og/mark.png'), '-resize', '84x84', ')', '-gravity', 'NorthWest', '-geometry', '+110+84', '-composite'];
        if ($brandImg) {
            array_push($args, $brandImg, '-geometry', '+216+112', '-composite');
        }
        array_push($args, '-fill', '#C9A96E', '-draw', 'rectangle 110,226 230,229');
        array_push($args, $titleImg, '-geometry', '+110+262', '-composite');
        $titleHeight = (int) trim(Process::run(['magick', 'identify', '-format', '%h', $titleImg])->output());
        array_push($args, $priceImg, '-geometry', '+110+'.(262 + $titleHeight + 28), '-composite');
        if ($metaImg) {
            array_push($args, $metaImg, '-gravity', 'SouthWest', '-geometry', '+110+84', '-composite');
        }
        array_push($args, '-strip', '-interlace', 'JPEG', '-quality', '86', $target);

        return Process::env($env)->run($args)->successful();
    }
}
