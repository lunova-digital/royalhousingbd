<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Listing\Models\Listing;
use Modules\Masterplan\Models\Masterplan;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        $this->admin = User::factory()->create(['email' => 'admin@example.com', 'password' => 'password']);
    }

    public function test_dashboard_counts(): void
    {
        $this->actingAs($this->admin)->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('listings.plot', 1)
            ->assertJsonPath('maps', 1)
            ->assertJsonPath('plots.available', 5);
    }

    public function test_listing_validation_and_crud(): void
    {
        $this->actingAs($this->admin);

        $this->postJson('/api/admin/listings', ['type' => 'flat', 'title' => '', 'location' => '', 'price' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'location', 'price']);

        $created = $this->postJson('/api/admin/listings', [
            'type' => 'flat', 'title' => 'Test flat', 'location' => 'Mirpur, Dhaka', 'call_for_price' => true,
            'size' => '1200 sqft', 'bedrooms' => 3, 'baths' => 2, 'floor' => '3rd',
            'facilities' => ['Lift', '', ' CCTV '], 'description' => 'Hi', 'published' => true,
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ])->assertCreated()->assertJsonPath('listing.slug', 'test-flat')->assertJsonPath('listing.facilities', ['Lift', 'CCTV']);
        $id = $created->json('listing.id');

        $this->get('/flat/test-flat')->assertOk()->assertSee('Call for Price');

        $this->putJson("/api/admin/listings/{$id}", ['type' => 'flat', 'title' => 'Renamed flat', 'location' => 'Mirpur', 'price' => '৳ 1 Crore', 'published' => false])
            ->assertOk()->assertJsonPath('listing.slug', 'renamed-flat');
        $this->get('/flat/renamed-flat')->assertNotFound();

        $this->putJson('/api/admin/listings/featured', ['ids' => [$id, 1]])->assertOk()->assertJsonPath('listings.0.id', $id);

        $this->deleteJson("/api/admin/listings/{$id}")->assertOk();
        $this->assertDatabaseMissing('listings', ['id' => $id]);
    }

    public function test_plots_bulk_save_upserts_and_deletes(): void
    {
        $this->actingAs($this->admin);
        $map = Masterplan::query()->first();
        $first = $map->plots->first();

        $this->putJson("/api/admin/masterplans/{$map->id}/plots", ['plots' => [
            ['id' => 0, 'number' => 'X1', 'status' => 'sold', 'polygon' => [['x' => 1, 'y' => 1], ['x' => 2, 'y' => 2]]],
        ]])->assertStatus(422)->assertJsonValidationErrors(['plots.0.polygon']);

        $this->putJson("/api/admin/masterplans/{$map->id}/plots", ['plots' => [
            ['id' => $first->id, 'number' => 'A1', 'status' => 'booked', 'size' => '4 katha', 'polygon' => $first->polygon],
            ['id' => 0, 'number' => 'Z9', 'status' => 'available', 'polygon' => [['x' => 1, 'y' => 1], ['x' => 2, 'y' => 1], ['x' => 2, 'y' => 2]]],
        ]])->assertOk()->assertJsonCount(2, 'masterplan.plots')->assertJsonPath('masterplan.plots.0.status', 'booked');

        $this->assertDatabaseCount('plots', 2);
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
    }
}
