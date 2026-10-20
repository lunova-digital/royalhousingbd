<?php

namespace Modules\Listing\Tests\Feature;

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Modules\Listing\Models\Listing;
use Modules\Listing\Support\OgImage;
use Tests\TestCase;

class ShareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DemoSeeder::class);
    }

    public function test_listing_pages_carry_server_rendered_open_graph_and_twitter_tags(): void
    {
        $l = Listing::query()->where('slug', 'corner-plot-block-c')->firstOrFail();
        $html = $this->get($l->url())->assertOk()->getContent();

        $meta = fn (string $attr, string $name) => preg_match('/<meta '.$attr.'="'.preg_quote($name, '/').'" content="([^"]*)"/', $html, $m) ? html_entity_decode($m[1]) : null;
        $this->assertSame('Corner plot, Block C — ৳ 35 Lakh / katha', $meta('property', 'og:title'));
        $this->assertStringContainsString('Hasara (Munshiganj)', $meta('property', 'og:description'));
        $this->assertStringContainsString('Plot', $meta('property', 'og:description'));
        $this->assertSame(url('/plot/corner-plot-block-c'), $meta('property', 'og:url'));
        $this->assertStringContainsString('/og/plot/corner-plot-block-c.jpg?v=', $meta('property', 'og:image'));
        $this->assertSame('1200', $meta('property', 'og:image:width'));
        $this->assertSame('630', $meta('property', 'og:image:height'));
        $this->assertSame('summary_large_image', $meta('name', 'twitter:card'));
        $this->assertSame($meta('property', 'og:image'), $meta('name', 'twitter:image'));
        $this->assertSame('Corner plot, Block C — ৳ 35 Lakh / katha', $meta('name', 'twitter:title'));
    }

    public function test_share_buttons_are_on_cards_and_detail_pages_with_the_popover(): void
    {
        $l = Listing::query()->where('slug', 'corner-plot-block-c')->firstOrFail();
        $this->get('/plot')->assertOk()->assertSee('data-share data-share-title="Corner plot, Block C"', false)->assertSee('data-share-pop', false)->assertSee('data-net="telegram"', false)->assertSee('data-net="copy"', false);
        $this->get($l->url())->assertOk()->assertSee('data-share-url="'.url($l->url()).'"', false)->assertSee('Messenger');
        $this->get($l->url().'?lang=bn')->assertOk()->assertSee('শেয়ার')->assertSee('লিংক কপি করুন');
    }

    public function test_the_preview_image_is_1200_by_630_and_small_enough_for_whatsapp(): void
    {
        if (! Process::run(['magick', '-version'])->successful()) {
            $this->markTestSkipped('ImageMagick is not installed.');
        }
        $l = Listing::query()->where('slug', 'corner-plot-block-c')->firstOrFail();
        $res = $this->get('/og/plot/corner-plot-block-c.jpg')->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $file = $res->baseResponse->getFile()->getPathname();
        [$w, $h] = getimagesize($file);
        $this->assertSame([1200, 630], [$w, $h]);
        $this->assertLessThan(300_000, filesize($file));
        $this->get('/og/plot/nope.jpg')->assertNotFound();
        $this->assertNotSame(sha1_file($file), sha1_file($this->generatedFor($l, ['title' => 'A very different title'])));
    }

    private function generatedFor(Listing $l, array $change): string
    {
        $l->update($change);
        $this->get('/og/plot/'.$l->fresh()->slug.'.jpg')->assertOk();

        return OgImage::path($l->fresh());
    }
}
