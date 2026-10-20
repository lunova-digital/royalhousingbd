<?php

namespace Tests\Feature;

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** The public site's EN / বাং switch (Modules\Site\Http\Middleware\SetLocale). */
class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DemoSeeder::class);
    }

    public function test_english_is_the_default(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertSee('Frequently asked questions')
            ->assertDontSee('সচরাচর জিজ্ঞাসা')
            ->assertDontSee('noto-sans-bengali')
            ->assertCookieMissing('lang');
    }

    public function test_lang_query_switches_to_bangla_and_sets_the_cookie(): void
    {
        $this->get('/?lang=bn')
            ->assertOk()
            ->assertSee('<html lang="bn"', false)
            ->assertPlainCookie('lang', 'bn')
            ->assertSee('anek-bangla-bengali-400')
            ->assertSee('সচরাচর জিজ্ঞাসা')
            ->assertSee('যাচাইকৃত কাগজপত্র')
            ->assertDontSee('Frequently asked questions');
    }

    public function test_the_choice_persists_on_the_next_request(): void
    {
        $this->withUnencryptedCookie('lang', 'bn')->get('/')
            ->assertOk()
            ->assertSee('<html lang="bn"', false)
            ->assertSee('সচরাচর জিজ্ঞাসা');

        $this->withUnencryptedCookie('lang', 'bn')->get('/about')
            ->assertOk()
            ->assertSee('আমাদের গল্প')
            ->assertSee('<title>আমাদের সম্পর্কে | ', false);
    }

    public function test_listing_index_renders_bangla_chrome_but_keeps_admin_content(): void
    {
        $this->withUnencryptedCookie('lang', 'bn')->get('/projects')
            ->assertOk()
            ->assertSee('এলাকা')
            ->assertSee('ফিল্টার')
            ->assertSee('জিজ্ঞাসা করুন')
            ->assertSee('Corner plot, Block C')
            ->assertDontSee('Browse by location');

        $this->withUnencryptedCookie('lang', 'bn')->get('/plot/corner-plot-block-c')
            ->assertOk()
            ->assertSee('হোয়াটসঅ্যাপে জিজ্ঞাসা করুন')
            ->assertSee('Corner plot, Block C');
    }

    public function test_switching_back_to_english_overrides_the_cookie(): void
    {
        $this->withUnencryptedCookie('lang', 'bn')->get('/?lang=en')
            ->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertPlainCookie('lang', 'en')
            ->assertSee('Frequently asked questions');
    }

    public function test_unknown_languages_are_ignored(): void
    {
        $this->get('/?lang=fr')
            ->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertCookieMissing('lang');

        $this->withUnencryptedCookie('lang', 'xx')->get('/')->assertSee('<html lang="en"', false);
    }

    public function test_the_header_offers_both_languages(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSee('/contact?lang=bn" hreflang="bn"', false)
            ->assertSee('/contact?lang=en" hreflang="en"', false)
            ->assertSee('বাং');
    }
}
