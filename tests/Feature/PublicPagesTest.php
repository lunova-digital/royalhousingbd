<?php

namespace Tests\Feature;

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Masterplan\Models\Masterplan;
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
        foreach (['/', '/about', '/projects', '/property', '/plot', '/flat', '/resort', '/five-star-hotel', '/map', '/gallery', '/contact', '/privacy', '/terms', '/robots.txt'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->get('/sitemap.xml')->assertOk()->assertSee('/plot/corner-plot-block-c')->assertSee('/five-star-hotel</loc>', false);
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
        $this->get('/flat?q=nothing-matches')->assertOk()->assertSee('No projects match');
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

    public function test_masterplan_pages_render_plots(): void
    {
        $this->get('/map')->assertOk()->assertSee('Royal City Phase 1')->assertSee('View Map');
        $masterplan = Masterplan::query()->first();
        $plot = $masterplan->plots->first();
        $this->get($masterplan->url())
            ->assertOk()
            ->assertSee('id="plan-data"', false)
            ->assertSee($plot->reference())
            ->assertSee('"key":"'.$plot->svg_key.'"', false)
            ->assertSee('"status":"available"', false)
            ->assertSee('.svg', false);
    }

    public function test_linked_listing_shows_plot_availability(): void
    {
        $this->get('/plot/corner-plot-block-c')->assertOk()->assertSee('plots available')->assertSee('Project Map');
        $this->get('/gallery')->assertOk()->assertSee('Masterplans');
    }

    public function test_unknown_pages_are_404(): void
    {
        $this->get('/nope')->assertNotFound();
        $this->get('/plot/does-not-exist')->assertNotFound();
        $this->get('/api/admin/dashboard')->assertUnauthorized();
    }
}
