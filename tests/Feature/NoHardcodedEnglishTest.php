<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Guards the EN / বাং switch: public copy must come from the lang files (__() / trans_choice()), never be
 * typed into a Blade view or a public script, or half the page stays English in বাং. Brand names, symbols
 * and the few allow-listed literals below are fine.
 */
class NoHardcodedEnglishTest extends TestCase
{
    /** Literal words that are not translatable copy (brands, units, placeholders). */
    private const ALLOWED = ['WhatsApp', 'Facebook', 'Instagram', 'YouTube', 'TikTok', 'Google', 'Plus', 'Jakarta', 'Sans', 'Inter', 'Hind', 'Siliguri', 'Royal', 'Housing', 'Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'XXXXXXXXX', 'BDT', 'Taka', 'Dhaka', 'Bangladesh', 'Bangla', 'English', 'Phone', 'Email', 'Website'];

    /** Public views and scripts, not admin (React, English by design) or mail templates. */
    private function files(string $glob): array
    {
        return array_values(array_filter(glob(base_path($glob)) ?: [], fn ($f) => ! str_contains($f, '/mail/') && ! str_contains($f, '/Auth/') && ! str_contains($f, 'dashboard') && ! str_contains($f, '/pages/')));
    }

    private function english(string $text): array
    {
        preg_match_all('/\b[A-Za-z][a-z]{2,}\b/', $text, $m);

        return array_values(array_diff(array_unique($m[0]), self::ALLOWED));
    }

    public function test_blade_views_have_no_literal_english_copy(): void
    {
        $views = [...$this->files('Modules/*/resources/views/*.blade.php'), ...$this->files('Modules/*/resources/views/*/*.blade.php'), ...$this->files('resources/views/errors/*.blade.php')];
        $this->assertNotEmpty($views);
        $offenders = [];
        foreach ($views as $file) {
            $src = File::get($file);
            $src = preg_replace(['/\{\{--.*?--\}\}/s', '/<script\b.*?<\/script>/is', '/<style\b.*?<\/style>/is', '/@php\b.*?@endphp/s', '/@php\(.*?\)\s*$/m', '/\{\{.*?\}\}/s', '/\{!!.*?!!\}/s', '/@\w+(?:\s*(\((?:[^()]++|(?1))*\)))?/s', '/<\?php.*?\?>/s'], ' ', $src);
            // Text between tags
            preg_match_all('/>([^<>]+)</', $src, $texts);
            foreach ($texts[1] as $t) {
                if ($w = $this->english($t)) {
                    $offenders[] = basename(dirname($file, 2)).'/'.basename($file).': '.trim(preg_replace('/\s+/', ' ', $t)).'  ['.implode(',', $w).']';
                }
            }
            // User-facing attributes
            preg_match_all('/\s(?:placeholder|aria-label|title|alt)="([^"{}]+)"/', $src, $attrs);
            foreach ($attrs[1] as $t) {
                if ($w = $this->english($t)) {
                    $offenders[] = basename(dirname($file, 2)).'/'.basename($file).': attr "'.$t.'"  ['.implode(',', $w).']';
                }
            }
        }
        $this->assertSame([], $offenders, "Move these into the lang files (en + bn):\n".implode("\n", $offenders));
    }

    public function test_public_scripts_have_no_literal_english_copy(): void
    {
        $offenders = [];
        foreach ([...$this->files('Modules/Site/resources/js/*.ts'), ...$this->files('Modules/Consultation/resources/js/consult.ts'), ...$this->files('Modules/Masterplan/resources/js/plan*.ts'), ...$this->files('Modules/Listing/resources/js/public*.ts')] as $file) {
            $src = preg_replace(['#//[^\n]*#', '#/\*.*?\*/#s'], '', File::get($file));
            // Strings that end up on screen: textContent / innerHTML / title / aria / placeholder assignments and label fallbacks.
            preg_match_all('/(?:textContent|innerHTML|\.title|placeholder|ariaLabel|label)\s*[:=]\s*[`\'"]([^`\'"$]{3,})[`\'"]|setAttribute\(\s*[\'"](?:aria-label|title|placeholder)[\'"]\s*,\s*[`\'"]([^`\'"$]+)[`\'"]/', $src, $m, PREG_SET_ORDER);
            foreach ($m as $hit) {
                $text = strip_tags($hit[1] !== '' ? $hit[1] : ($hit[2] ?? ''));
                if ($w = $this->english($text)) {
                    $offenders[] = basename($file).': "'.$text.'"  ['.implode(',', $w).']';
                }
            }
        }
        $this->assertSame([], $offenders, "Pass these from Blade via the lang files:\n".implode("\n", $offenders));
    }
}
