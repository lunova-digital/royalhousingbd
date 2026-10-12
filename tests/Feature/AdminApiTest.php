<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Listing\Models\Feature;
use Modules\Listing\Models\Listing;
use Modules\Masterplan\Models\Masterplan;
use Modules\Masterplan\Models\Plot;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DemoSeeder::class);
        $this->admin = User::factory()->create(['email' => 'admin@example.com', 'password' => 'password']);
    }

    public function test_dashboard_counts(): void
    {
        $this->actingAs($this->admin)->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'plot', 'name' => 'Plot', 'count' => 1])
            ->assertJsonPath('maps', 4)
            ->assertJsonPath('plots.available', Plot::query()->where('status', 'available')->count());
    }

    public function test_listing_validation_and_crud(): void
    {
        $this->actingAs($this->admin);

        $this->postJson('/api/admin/listings', ['type' => 'flat', 'title' => '', 'location' => '', 'price' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'location', 'price']);

        $this->postJson('/api/admin/listings', ['type' => 'villa', 'title' => 'x', 'location' => 'y', 'price' => '1'])
            ->assertStatus(422)->assertJsonValidationErrors(['type']);

        $lift = Feature::query()->where('slug', 'lift')->value('id');
        $cctv = Feature::query()->where('slug', 'cctv')->value('id');
        $created = $this->postJson('/api/admin/listings', [
            'type' => 'flat', 'title' => 'Test flat', 'location' => 'Mirpur, Dhaka', 'call_for_price' => true,
            'size' => '1200 sqft', 'bedrooms' => 3, 'baths' => 2, 'floor' => '3rd',
            'features' => [$lift, $cctv, $lift], 'description' => 'Hi', 'published' => true,
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ])->assertCreated()->assertJsonPath('listing.slug', 'test-flat')->assertJsonPath('listing.type_label', 'Flat')->assertJsonCount(2, 'listing.features');
        $id = $created->json('listing.id');

        $this->get('/flat/test-flat')->assertOk()->assertSee('Call for Price')->assertSee('CCTV');

        $this->putJson("/api/admin/listings/{$id}", ['type' => 'flat', 'title' => 'Renamed flat', 'location' => 'Mirpur', 'price' => '৳ 1 Crore', 'published' => false, 'features' => [$cctv]])
            ->assertOk()->assertJsonPath('listing.slug', 'renamed-flat')->assertJsonPath('listing.features.0.slug', 'cctv')->assertJsonCount(1, 'listing.features');
        $this->get('/flat/renamed-flat')->assertNotFound();

        $this->putJson('/api/admin/listings/featured', ['ids' => [$id, 1]])->assertOk()->assertJsonPath('listings.0.id', $id);

        $this->deleteJson("/api/admin/listings/{$id}")->assertOk();
        $this->assertDatabaseMissing('listings', ['id' => $id]);
    }

    public function test_listing_types_are_managed_and_drive_public_routes(): void
    {
        $this->actingAs($this->admin);

        $this->getJson('/api/admin/listing-types')->assertOk()->assertJsonPath('types.0.slug', 'property')->assertJsonPath('types.3.slug', 'resort')->assertJsonPath('types.4.name', 'Five Star Hotel');

        // Reserved addresses and duplicates are refused.
        $this->postJson('/api/admin/listing-types', ['name' => 'About'])->assertStatus(422)->assertJsonValidationErrors(['name']);
        $this->postJson('/api/admin/listing-types', ['name' => 'Plot'])->assertStatus(422)->assertJsonValidationErrors(['name']);

        $this->get('/commercial-space')->assertNotFound();
        $created = $this->postJson('/api/admin/listing-types', ['name' => 'Commercial space'])->assertCreated()->assertJsonPath('type.slug', 'commercial-space');
        $typeId = $created->json('type.id');
        $this->get('/commercial-space')->assertOk()->assertSee('Commercial space listings');
        $this->get('/')->assertOk()->assertSee('href="/commercial-space"', false);

        $this->putJson("/api/admin/listing-types/{$typeId}", ['name' => 'Commercial'])->assertOk()->assertJsonPath('type.name', 'Commercial')->assertJsonPath('type.slug', 'commercial-space');
        $this->putJson('/api/admin/listing-types/order', ['ids' => [$typeId]])->assertOk()->assertJsonPath('types.0.slug', 'commercial-space');

        $listing = $this->postJson('/api/admin/listings', ['type' => 'commercial-space', 'title' => 'Shop unit', 'location' => 'Gulshan', 'price' => '৳ 2 Crore', 'published' => true])
            ->assertCreated()->assertJsonPath('listing.type_label', 'Commercial');
        $this->get('/commercial-space/shop-unit')->assertOk()->assertSee('Shop unit');
        $this->deleteJson("/api/admin/listing-types/{$typeId}")->assertStatus(422);
        $this->deleteJson('/api/admin/listings/'.$listing->json('listing.id'))->assertOk();
        $this->deleteJson("/api/admin/listing-types/{$typeId}")->assertOk();
        $this->get('/commercial-space')->assertNotFound();
    }

    public function test_features_are_managed_and_detached_on_delete(): void
    {
        $this->actingAs($this->admin);

        $this->postJson('/api/admin/features', ['name' => 'Lift'])->assertStatus(422)->assertJsonValidationErrors(['name']);
        $created = $this->postJson('/api/admin/features', ['name' => 'Solar backup'])->assertCreated()->assertJsonPath('feature.slug', 'solar-backup');
        $id = $created->json('feature.id');

        $plot = Listing::query()->where('type', 'plot')->first();
        $plot->features()->attach($id);
        $this->putJson("/api/admin/features/{$id}", ['name' => 'Solar power'])->assertOk()->assertJsonPath('feature.slug', 'solar-power')->assertJsonPath('feature.listings_count', 1);
        $this->get('/plot?features%5B%5D=solar-power')->assertOk()->assertSee('Corner plot');

        $this->deleteJson("/api/admin/features/{$id}")->assertOk();
        $this->assertDatabaseMissing('feature_listing', ['feature_id' => $id]);
        $this->assertDatabaseMissing('features', ['id' => $id]);
    }

    public function test_plots_bulk_save_upserts_and_deletes(): void
    {
        $this->actingAs($this->admin);
        $map = Masterplan::query()->first();
        [$first, $second] = $map->layout_keys;

        // A plot must belong to a group of the layout.
        $this->putJson("/api/admin/masterplans/{$map->id}/plots", ['plots' => [
            ['svg_key' => 'not-in-layout', 'number' => 'X1', 'status' => 'sold'],
        ]])->assertStatus(422)->assertJsonValidationErrors(['plots.0.svg_key']);

        $this->putJson("/api/admin/masterplans/{$map->id}/plots", ['plots' => [
            ['svg_key' => $first, 'number' => 'A1', 'status' => 'booked', 'size' => '4 katha'],
            ['svg_key' => $second, 'number' => 'Z9', 'status' => 'available'],
        ]])->assertOk()->assertJsonCount(2, 'masterplan.plots')->assertJsonPath('masterplan.plots.0.status', 'booked');

        $this->assertSame(2, $map->plots()->count());
        $this->assertDatabaseHas('plots', ['svg_key' => $first, 'number' => 'A1']);
    }

    public function test_masterplan_is_created_from_an_svg_layout(): void
    {
        $this->actingAs($this->admin);

        $noPlots = UploadedFile::fake()->createWithContent('plain.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10"/></svg>');
        $this->post('/api/admin/masterplans', ['name' => 'Phase 2', 'layout' => $noPlots], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['layout']);

        $this->postJson('/api/admin/masterplans', ['name' => 'Phase 2'])
            ->assertStatus(422)->assertJsonValidationErrors(['layout']);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200px" height="100px">'
            .'<script>alert(1)</script>'
            .'<g class="plan-plot" id="r1-01" onclick="alert(1)"><rect width="10" height="10"/><text>01</text></g>'
            .'<g class="roads plan-plot"><rect x="20" width="10" height="10"/></g>'
            .'<g class="plan-plot" id="r1-01"><rect x="40" width="10" height="10"/></g>'
            .'</svg>';
        $response = $this->post('/api/admin/masterplans', [
            'name' => 'Phase 2', 'description' => 'East of the lake', 'published' => '1',
            'layout' => UploadedFile::fake()->createWithContent('layout.svg', $svg),
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('masterplan.published', true)
            ->assertJsonPath('masterplan.image_width', 200)
            ->assertJsonPath('masterplan.image_height', 100)
            ->assertJsonPath('masterplan.layout_keys', ['plot-1', 'plot-2', 'plot-3']);

        $stored = Storage::disk('public')->get($response->json('masterplan.image_path'));
        $this->assertStringContainsString('viewBox="0 0 200 100"', $stored);
        // The file's own id is replaced by the assigned key.
        $this->assertStringContainsString('id="plot-1" data-plot="plot-1"', $stored);
        $this->assertStringNotContainsString('r1-01', $stored);
        $this->assertStringContainsString('id="plot-2" data-plot="plot-2"', $stored);
        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('onclick', $stored);

        // Replacing the layout keeps details only for groups that still exist.
        $id = $response->json('masterplan.id');
        $saved = $this->putJson("/api/admin/masterplans/{$id}/plots", ['plots' => [
            ['svg_key' => 'plot-1', 'number' => '01', 'status' => 'sold'],
            ['svg_key' => 'plot-2', 'number' => '02', 'status' => 'available'],
        ]])->assertOk();
        $stamped = Storage::disk('public')->get($response->json('masterplan.image_path'));
        $this->assertStringContainsString('data-plot="plot-1" data-plot-id="'.$saved->json('masterplan.plots.0.id').'"', $stamped);
        $this->assertStringContainsString('data-plot="plot-2" data-plot-id="'.$saved->json('masterplan.plots.1.id').'"', $stamped);
        $this->assertStringNotContainsString('data-plot="plot-3" data-plot-id', $stamped);
        $smaller = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50"><g class="plan-plot" id="r1-01"><rect width="10" height="10"/></g></svg>';
        $this->post("/api/admin/masterplans/{$id}/layout", ['layout' => UploadedFile::fake()->createWithContent('v2.svg', $smaller)], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('masterplan.layout_keys', ['plot-1'])
            ->assertJsonCount(1, 'masterplan.plots')
            ->assertJsonPath('masterplan.plots.0.number', '01');
        $this->assertFalse(Storage::disk('public')->exists($response->json('masterplan.image_path')));
        $this->assertStringContainsString('data-plot-id="'.$saved->json('masterplan.plots.0.id').'"', Storage::disk('public')->get(Masterplan::query()->find($id)->image_path));
    }

    public function test_settings_only_accept_known_keys(): void
    {
        $this->actingAs($this->admin)->putJson('/api/admin/settings', ['settings' => ['phone' => '+880 1999', 'bogus' => 'x']])
            ->assertOk()->assertJsonPath('settings.phone', '+880 1999')->assertJsonMissingPath('settings.bogus');
        $this->assertDatabaseMissing('settings', ['key' => 'bogus']);
        $this->get('/about')->assertOk()->assertSee('Our Story');
    }

    public function test_ticker_and_videos_replace_lists(): void
    {
        $this->actingAs($this->admin);
        $this->putJson('/api/admin/ticker', ['items' => [['text' => 'Hello', 'link' => '', 'active' => true], ['text' => '']]])
            ->assertStatus(422);
        $this->putJson('/api/admin/ticker', ['items' => [['text' => 'Hello', 'link' => '/plot', 'active' => true]]])
            ->assertOk()->assertJsonCount(1, 'items');
        $this->putJson('/api/admin/videos', ['items' => [['title' => '', 'url' => 'not a link']]])->assertStatus(422);
        $this->putJson('/api/admin/videos', ['items' => [['title' => '', 'url' => 'https://youtube.com/shorts/abcdefghijk']]])
            ->assertOk()->assertJsonCount(1, 'items');
        $this->get('/')->assertSee('Hello');
    }

    public function test_unpublished_listing_is_hidden_from_public_but_counted(): void
    {
        Listing::query()->where('type', 'plot')->update(['published' => false]);
        $this->get('/plot')->assertOk()->assertSee('No projects match');
        $this->actingAs($this->admin)->getJson('/api/admin/listings?type=plot')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/admin/listings?q=paved')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('listings.0.type', 'plot');
    }
}
