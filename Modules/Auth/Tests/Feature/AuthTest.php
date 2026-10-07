<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create(['email' => 'admin@example.com', 'password' => 'password']);
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/admin/listings/1')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Admin sign in');
    }

    public function test_login_logout_and_bad_credentials(): void
    {
        $this->from('/login')->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($this->admin);
        $this->get('/login')->assertRedirect('/admin');

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_the_admin_mounts_with_every_dashboard_script(): void
    {
        $this->actingAs($this->admin)->get('/admin/listings/1')
            ->assertOk()
            ->assertSee('id="admin"', false)
            ->assertViewHas('entries', fn (array $entries) => in_array('Modules/Listing/resources/js/dashboard.tsx', $entries, true)
                && end($entries) === 'vendor/protibimbok/laramod-admin/resources/js/main.tsx')
            ->assertViewHas('boot', fn (array $boot) => $boot['basePath'] === '/admin' && str_ends_with($boot['logoutUrl'], '/logout'));
    }

    public function test_password_change_needs_the_current_password(): void
    {
        $this->actingAs($this->admin);

        $this->putJson('/api/auth/password', ['current_password' => 'nope', 'password' => 'new-password'])
            ->assertStatus(422)->assertJsonValidationErrors(['current_password']);
        $this->putJson('/api/auth/password', ['current_password' => 'password', 'password' => 'new-password'])->assertOk();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong'])->assertRedirect();
        }
        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong'])->assertStatus(429);
    }
}
