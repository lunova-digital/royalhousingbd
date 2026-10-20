<?php

namespace Tests\Feature;

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Masterplan\Models\Masterplan;
use Modules\Settings\Settings;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DemoSeeder::class);
    }

    public function test_every_sitemap_page_renders(): void
    {
        foreach (['/', '/about', '/projects', '/property', '/plot', '/flat', '/resort', '/five-star-hotel', '/map', '/gallery', '/contact', '/consultation', '/privacy', '/terms', '/robots.txt'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->get('/sitemap.xml')->assertOk()->assertSee('/plot/corner-plot-block-c')->assertSee('/consultation</loc>', false)->assertSee('/five-star-hotel</loc>', false);
    }

    public function test_listing_detail_has_whatsapp_reference(): void
    {
        $this->get('/plot/corner-plot-block-c')
            ->assertOk()
            ->assertSee('Corner plot, Block C')
            ->assertSee('Ref%3A%20PLOT-', false)
            ->assertSee('wa.me/8801700000000', false);
    }

    public function test_listing_grid_filters_by_location_and_search(): void
    {
        $this->get('/listings')->assertRedirect('/projects');
        $this->get('/projects?location=Hasara+%28Munshiganj%29')->assertOk()->assertSee('Corner plot')->assertDontSee('Duplex house');
        $this->get('/plot')->assertOk()->assertSee('Narayanganj');
        $this->get('/flat?q=lake')->assertOk()->assertSee('1450 sqft flat');
        $this->get('/flat?q=nothing-matches')->assertOk()->assertSee('No exact match');
    }

    public function test_keyword_search_matches_features_types_and_project_names(): void
    {
        // A feature name, the masterplan the plot belongs to, a type name, and several words together.
        $this->get('/projects?q=gas')->assertOk()->assertSee('Corner plot')->assertDontSee('Duplex house');
        $this->get('/projects?q=royal+city')->assertOk()->assertSee('Corner plot')->assertDontSee('Duplex house');
        $this->get('/projects?q=five+star')->assertOk()->assertSee('Beachfront hotel')->assertDontSee('Corner plot');
        $this->get('/projects?q=lift+mirpur')->assertOk()->assertSee('1450 sqft flat')->assertDontSee('Beachfront hotel');
    }

    public function test_feature_filter_requires_every_ticked_feature(): void
    {
        $this->get('/projects?features%5B%5D=generator')->assertOk()->assertSee('Duplex house')->assertSee('1450 sqft flat')->assertSee('Hilltop eco resort');
        $this->get('/projects?features%5B%5D=generator&features%5B%5D=lift')->assertOk()->assertSee('1450 sqft flat')->assertDontSee('Duplex house');
        $this->get('/projects?features%5B%5D=not-a-feature')->assertOk()->assertSee('Duplex house');
        $this->get('/plot')->assertOk()->assertSee('name="features[]"', false)->assertSee('Boundary wall')->assertDontSee('Swimming pool');
    }

    public function test_budget_range_filter_compares_the_taka_amount(): void
    {
        // Demo prices: duplex ৳ 4.5 Crore, corner plot ৳ 35 Lakh / katha, resort share ৳ 25 Lakh; the flat is "call for price".
        $this->get('/projects?budget_max=5000000')->assertOk()->assertSee('Corner plot')->assertSee('Hilltop eco resort')->assertDontSee('Duplex house')->assertDontSee('1450 sqft flat');
        $this->get('/projects?budget_min=10000000')->assertOk()->assertSee('Duplex house')->assertDontSee('Corner plot');
        $this->get('/projects?budget_min=3000000&budget_max=4000000')->assertOk()->assertSee('Corner plot')->assertDontSee('Hilltop eco resort');
        // Swapped bounds are put in order; bounds are capped at 100 crore; junk is ignored.
        $this->get('/projects?budget_min=4000000&budget_max=3000000')->assertOk()->assertSee('Corner plot')->assertDontSee('Hilltop eco resort');
        $this->get('/projects?budget_max=99999999999')->assertOk()->assertSee('Duplex house');
        $this->get('/projects?budget_min=abc')->assertOk()->assertSee('1450 sqft flat');
        // The select keeps its choice; the budget labels follow the language.
        $this->get('/projects?budget_max=5000000')->assertSee('value="5000000" selected', false);
        $this->get('/projects?budget_max=5000000&lang=bn')->assertOk()->assertSee('৳ ৫০ লাখ')->assertSee('বাজেট');
    }

    public function test_masterplan_pages_render_plots(): void
    {
        $this->get('/map')->assertOk()->assertSee('Royal City Phase 1')->assertSee('Open map');
        $masterplan = Masterplan::query()->first();
        $plot = $masterplan->plots->first();
        $this->get($masterplan->url())
            ->assertOk()
            ->assertSee('id="plan-data"', false)
            ->assertSee($plot->reference())
            ->assertSee('"key":"'.$plot->svg_key.'"', false)
            ->assertSee('"status":"available"', false)
            // The WhatsApp message is composed in the browser from the selection.
            ->assertSee('"whatsapp":{"number":"8801700000000"', false)
            ->assertSee('msgMany', false)
            ->assertSee('template data-plot-item', false)
            ->assertSee('.svg', false);
    }

    public function test_linked_listing_shows_plot_availability(): void
    {
        $this->get('/plot/corner-plot-block-c')->assertOk()->assertSee('plots open')->assertSee('Project Map');
        $this->get('/gallery')->assertOk()->assertSee('Masterplans');
    }

    public function test_unknown_pages_are_404(): void
    {
        $this->get('/nope')->assertNotFound();
        $this->get('/plot/does-not-exist')->assertNotFound();
        $this->get('/api/admin/dashboard')->assertUnauthorized();
    }

    public function test_filter_bar_asks_for_the_results_block_only(): void
    {
        $page = $this->get('/projects')->assertOk()->assertSee('data-filter-root', false)->assertSee('<html', false);
        $fragment = $this->get('/projects?fragment=1&type=plot')->assertOk()->assertSee('data-results-inner', false)->assertSee('data-total="1"', false)->assertSee('Corner plot')->assertDontSee('<html', false);
        $this->get('/projects?fragment=1&q=nothing-matches')->assertOk()->assertSee('No exact match')->assertSee('wa.me', false);
    }

    public function test_several_locations_and_price_sort(): void
    {
        $this->get('/projects?fragment=1&locations%5B%5D=Uttara&locations%5B%5D=Hasara+%28Munshiganj%29')->assertOk()->assertSee('Duplex house')->assertSee('Corner plot')->assertDontSee('Beachfront hotel');
        $this->get('/projects?fragment=1&location=Uttara')->assertOk()->assertSee('Duplex house')->assertDontSee('Corner plot');
        $this->get('/projects?fragment=1&sort=price_asc')->assertOk()->assertSeeInOrder(['Hilltop eco resort', 'Corner plot', 'Duplex house']);
        $this->get('/projects?fragment=1&sort=price_desc')->assertOk()->assertSeeInOrder(['Duplex house', 'Corner plot', 'Hilltop eco resort']);
    }

    public function test_floating_button_options_follow_the_admin_toggles(): void
    {
        $this->get('/')->assertOk()->assertSee('data-chooser-toggle', false)->assertSee('Book free consultation');
        Settings::save(['float_whatsapp' => '0']);
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('btn-wa w-full lg:hidden', $html);
        $this->assertStringNotContainsString('btn-consult w-full lg:hidden', $html);
        Settings::save(['float_consult' => '0']);
        // Both off: no floating button on desktop, but the header consult button stays.
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('data-fab', $html);
        $this->assertStringContainsString('consult-glow btn-sm', $html);
    }

    public function test_project_switcher_gets_the_plan_as_json(): void
    {
        $masterplan = Masterplan::query()->first();
        $json = $this->getJson($masterplan->url().'?json=1')->assertOk()->assertJsonPath('slug', $masterplan->slug)->assertJsonPath('counts.available', $masterplan->statusCounts()['available'])->json();
        $this->assertSame($masterplan->plots->count(), count($json['plots']));
        $this->assertArrayHasKey('sizeRaw', $json['plots'][0]);
        $this->get($masterplan->url())->assertOk()->assertSee('data-plan-project', false)->assertSee('data-plot-consult', false)->assertSee('data-grip', false);
    }
}
