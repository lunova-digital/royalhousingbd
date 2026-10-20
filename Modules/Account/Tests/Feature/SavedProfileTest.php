<?php

namespace Modules\Account\Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Mockery;
use Modules\Account\Models\Member;
use Modules\Account\Models\SavedListing;
use Modules\Listing\Models\Listing;
use Tests\TestCase;

class SavedProfileTest extends TestCase
{
    use RefreshDatabase;

    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DemoSeeder::class);
        $this->member = Member::query()->create(['email' => 'm@example.com', 'name' => 'Mina Rahman', 'password' => 'secret-pass']);
    }

    private function login(): void
    {
        $this->postJson('/login', ['identifier' => 'm@example.com', 'password' => 'secret-pass'])->assertOk();
    }

    public function test_guests_cannot_save_but_the_page_offers_the_auth_sheet(): void
    {
        $listing = Listing::query()->published()->first();
        $this->putJson("/account/saved/{$listing->id}")->assertUnauthorized();
        $this->get($listing->url())->assertOk()->assertSee('data-save="'.$listing->id.'"', false)->assertSee('Log in to save this property')->assertSee('data-auth-dialog', false);
        $this->get('/projects')->assertOk()->assertSee('data-save=', false);
    }

    public function test_saving_is_idempotent_and_shows_on_cards_and_the_saved_page(): void
    {
        $this->login();
        [$plot, $flat] = [Listing::query()->where('type', 'plot')->first(), Listing::query()->where('type', 'flat')->first()];

        $this->putJson("/account/saved/{$plot->id}")->assertOk()->assertJson(['saved' => true, 'count' => 1]);
        $this->putJson("/account/saved/{$plot->id}")->assertOk()->assertJson(['saved' => true, 'count' => 1]);
        $this->putJson("/account/saved/{$flat->id}")->assertOk()->assertJson(['count' => 2]);
        $this->assertSame(2, SavedListing::query()->count());

        // The heart is filled on cards and detail pages for what is saved
        $this->get('/plot')->assertOk()->assertSee('data-save="'.$plot->id.'" aria-pressed="true"', false);
        $this->get($flat->url())->assertOk()->assertSee('data-save="'.$flat->id.'" aria-pressed="true"', false);

        $page = $this->get('/account/saved')->assertOk()->assertSee($plot->title)->assertSee($flat->title)->assertSee('data-saved-tab="plot"', false)->assertSee('data-saved-tab="flat"', false)->assertDontSee('data-saved-tab="resort"', false)
            ->assertSee('wa.me/8801700000000', false);
        $page->assertSee('Ask about all saved');

        $this->deleteJson("/account/saved/{$plot->id}")->assertOk()->assertJson(['saved' => false, 'count' => 1]);
        $this->get('/account/saved')->assertOk()->assertDontSee($plot->title)->assertSee($flat->title);
        $this->deleteJson("/account/saved/{$flat->id}")->assertOk();
        $this->get('/account/saved')->assertOk()->assertSee('Nothing saved yet')->assertSee('Browse listings');
    }

    public function test_unpublished_listings_cannot_be_saved(): void
    {
        $this->login();
        $draft = Listing::query()->first();
        $draft->update(['published' => false]);
        $this->putJson("/account/saved/{$draft->id}")->assertNotFound();
    }

    public function test_admin_sees_the_save_count_per_listing(): void
    {
        $listing = Listing::query()->published()->first();
        SavedListing::query()->create(['member_id' => $this->member->id, 'listing_id' => $listing->id]);
        $other = Member::query()->create(['phone' => '+8801712345678', 'password' => 'secret-pass']);
        SavedListing::query()->create(['member_id' => $other->id, 'listing_id' => $listing->id]);

        $this->actingAs(User::factory()->create());
        $rows = collect($this->getJson('/api/admin/listings')->assertOk()->json('listings'));
        $this->assertSame(2, $rows->firstWhere('id', $listing->id)['saved_by_count']);
        $this->assertSame(0, $rows->firstWhere('id', '!=', $listing->id)['saved_by_count']);
    }

    public function test_google_login_completes_a_pending_save(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 's']);
        $listing = Listing::query()->published()->first();
        $this->postJson('/account/pending-save', ['listing' => $listing->id])->assertOk();

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn((new SocialUser)->map(['id' => 'g-9', 'name' => 'G', 'email' => 'g@example.com']));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
        $this->get('/auth/google/callback')->assertRedirect('/');
        $this->assertTrue(SavedListing::query()->where('listing_id', $listing->id)->whereHas('member', fn ($q) => $q->where('email', 'g@example.com'))->exists());
    }

    public function test_profile_fields_are_optional_but_one_login_identifier_must_stay(): void
    {
        $this->login();
        $this->get('/account/profile')->assertOk()->assertSee('Mina Rahman')->assertSee('data-crop-dialog', false);

        $this->put('/account/profile', ['name' => '', 'email' => 'm@example.com', 'phone' => '', 'address' => ''])->assertSessionHasNoErrors();
        $this->put('/account/profile', ['name' => 'Mina', 'email' => '', 'phone' => '+880 1712-345678', 'address' => 'Dhaka'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('members', ['id' => $this->member->id, 'email' => null, 'phone' => '+8801712345678', 'address' => 'Dhaka']);

        // Removing the last identifier is refused; bad and taken values are explained
        $this->put('/account/profile', ['name' => 'Mina', 'email' => '', 'phone' => ''])->assertSessionHasErrors('email');
        $this->put('/account/profile', ['phone' => '12345'])->assertSessionHasErrors('phone');
        Member::query()->create(['email' => 'taken@example.com', 'password' => 'secret-pass']);
        $this->put('/account/profile', ['email' => 'taken@example.com', 'phone' => '01712345678'])->assertSessionHasErrors('email');
    }

    public function test_avatar_is_stored_as_a_256px_webp_and_can_be_removed(): void
    {
        $this->login();
        // 1x1 PNG
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $res = $this->post('/account/profile/avatar', ['avatar' => UploadedFile::fake()->createWithContent('me.png', $png)], ['Accept' => 'application/json'])->assertOk()->json();
        $path = $this->member->fresh()->avatar;
        $this->assertStringStartsWith('avatars/'.$this->member->id.'-', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString('/storage/avatars/', $res['url']);
        if (str_ends_with($path, '.webp')) {
            $size = getimagesize(Storage::disk('public')->path($path));
            $this->assertSame([256, 256], [$size[0], $size[1]]);
        }

        $this->post('/account/profile/avatar', ['avatar' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->deleteJson('/account/profile/avatar')->assertOk();
        $this->assertNull($this->member->fresh()->avatar);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_password_change_needs_the_current_one_but_google_only_members_can_just_set_one(): void
    {
        $this->login();
        $this->put('/account/profile/password', ['current_password' => 'nope', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])->assertSessionHasErrors('current_password');
        $this->put('/account/profile/password', ['current_password' => 'secret-pass', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])->assertSessionHasNoErrors();
        $this->post('/logout');
        $this->postJson('/login', ['identifier' => 'm@example.com', 'password' => 'brand-new-pass'])->assertOk();

        $this->post('/logout');
        $google = Member::query()->create(['email' => 'g@example.com', 'google_id' => 'g-1']);
        $this->assertFalse($google->hasPassword());
        $this->actingAs($google, 'member');
        $this->put('/account/profile/password', ['password' => 'first-password', 'password_confirmation' => 'first-password'])->assertSessionHasNoErrors();
        $this->assertTrue($google->fresh()->hasPassword());
    }
}
