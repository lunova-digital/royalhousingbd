<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Content\Models\Translation;
use Modules\Content\Services\Translations;
use Modules\Listing\Models\Listing;
use Modules\Site\Site;
use Tests\TestCase;

class BanglaContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DemoSeeder::class);
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    private User $admin;

    public function test_admin_bangla_text_replaces_english_only_on_the_bangla_site(): void
    {
        $listing = Listing::query()->where('slug', 'corner-plot-block-c')->firstOrFail();
        $this->putJson('/api/admin/translations', ['items' => [
            ['group' => 'listing', 'key' => (string) $listing->id, 'field' => 'title', 'value' => 'কর্নার প্লট, ব্লক সি'],
            ['group' => 'location', 'key' => 'Hasara (Munshiganj)', 'field' => 'value', 'value' => 'হাসারা (মুন্সিগঞ্জ)'],
        ]])->assertOk();

        auth()->logout();
        $this->get('/plot?lang=bn')->assertOk()->assertSee('কর্নার প্লট, ব্লক সি')->assertSee('হাসারা (মুন্সিগঞ্জ)');
        // The location filter keeps working on the English key while the label is Bangla.
        $this->get('/projects?location=Hasara+%28Munshiganj%29&lang=bn')->assertOk()->assertSee('কর্নার প্লট, ব্লক সি')->assertDontSee('Duplex house');
        // English and the admin API keep the stored text.
        $this->get('/plot?lang=en')->assertOk()->assertSee('Corner plot, Block C')->assertDontSee('কর্নার প্লট, ব্লক সি');
        $this->actingAs($this->admin)->getJson('/api/admin/listings')->assertOk()->assertSee('Corner plot, Block C');
    }

    public function test_digits_and_units_follow_the_language(): void
    {
        app()->setLocale('bn');
        $this->assertSame('৳ ৩৫ লাখ / কাঠা', Site::num('৳ 35 Lakh / katha'));
        app()->setLocale('en');
        $this->assertSame('৳ 35 Lakh / katha', Site::num('৳ 35 Lakh / katha'));

        auth()->logout();
        $this->get('/plot?lang=bn')->assertOk()->assertSee('৳ ৩৫ লাখ / কাঠা')->assertSee('© ২০');
    }

    public function test_free_strings_and_settings_translate(): void
    {
        Translation::query()->create(['group' => 'string', 'key' => 'Happy families', 'value' => 'সুখী পরিবার']);
        Translation::query()->create(['group' => 'setting', 'key' => 'hero_badge', 'value' => '২০১০ সাল থেকে বিশ্বস্ত']);
        Translations::flush();
        auth()->logout();
        $this->get('/?lang=bn')->assertOk()->assertSee('সুখী পরিবার')->assertSee('২০১০ সাল থেকে বিশ্বস্ত');
        $this->get('/?lang=en')->assertOk()->assertSee('Happy families')->assertSee('Trusted since 2010');
    }

    public function test_gaps_report_lists_what_is_missing(): void
    {
        $this->artisan('translations:gaps', ['--path' => 'storage/framework/testing/gaps.md'])->assertSuccessful();
        $md = file_get_contents(base_path('storage/framework/testing/gaps.md'));
        $this->assertStringContainsString('Corner plot, Block C', $md);
    }

    public function test_bangla_digits_leave_html_entities_alone(): void
    {
        $this->assertSame('Cox&#039;s ১২ &#x27;', Site::localizeHtml('Cox&#039;s 12 &#x27;'));
    }
}
