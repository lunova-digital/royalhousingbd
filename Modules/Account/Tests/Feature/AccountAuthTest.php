<?php

namespace Modules\Account\Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Mockery;
use Modules\Account\Models\Member;
use Modules\Account\Models\PhoneOtp;
use Modules\Account\Notifications\MemberResetPassword;
use Modules\Account\Support\Identifier;
use Modules\Account\Support\Sms\SmsSender;
use Tests\TestCase;

class AccountAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DemoSeeder::class);
    }

    public function test_identifier_detects_email_or_bd_mobile_and_normalises(): void
    {
        foreach (['01712345678', '8801712345678', '+8801712345678', '+880 1712-345 678', '০১৭১২৩৪৫৬৭৮'] as $raw) {
            $this->assertSame(['type' => 'phone', 'value' => '+8801712345678'], Identifier::parse($raw), $raw);
        }
        $this->assertSame(['type' => 'email', 'value' => 'a@b.com'], Identifier::parse('  A@B.com '));
        foreach (['', '12345', '02-1234567', '01212345678', 'not an email@', '+1 415 555 0100'] as $bad) {
            $this->assertNull(Identifier::parse($bad), $bad);
        }
        $this->assertSame('01712345678', Identifier::local('+8801712345678'));
    }

    public function test_register_with_email_and_with_mobile_then_log_in(): void
    {
        $this->postJson('/register', ['identifier' => 'Sara@Example.com', 'password' => 'secret-pass', 'name' => 'Sara'])->assertOk()->assertJson(['ok' => true]);
        $this->assertAuthenticated('member');
        $this->assertDatabaseHas('members', ['email' => 'sara@example.com', 'name' => 'Sara']);
        $this->post('/logout');

        $this->postJson('/register', ['identifier' => '01812345678', 'password' => 'secret-pass'])->assertOk();
        $this->assertDatabaseHas('members', ['phone' => '+8801812345678', 'email' => null]);
        $this->post('/logout');
        $this->assertGuest('member');

        // Log in with any spelling of the same number, or with the email
        $this->postJson('/login', ['identifier' => '+8801812345678', 'password' => 'secret-pass'])->assertOk();
        $this->post('/logout');
        $this->postJson('/login', ['identifier' => 'sara@example.com', 'password' => 'secret-pass', 'remember' => '1'])->assertOk();
        $this->assertAuthenticated('member');

        // Duplicates, short passwords and junk identifiers are refused with translated messages
        $this->post('/logout');
        $this->postJson('/register', ['identifier' => 'sara@example.com', 'password' => 'secret-pass'])->assertUnprocessable()->assertJsonValidationErrors('identifier');
        $this->postJson('/register', ['identifier' => 'x@y.com', 'password' => 'short'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson('/register?lang=bn', ['identifier' => 'nope', 'password' => 'secret-pass'])->assertUnprocessable()->assertJsonPath('errors.identifier.0', fn ($m) => str_contains($m, 'ইমেইল'));
    }

    public function test_bad_logins_do_not_reveal_whether_an_account_exists_and_are_rate_limited(): void
    {
        Member::query()->create(['email' => 'real@example.com', 'password' => 'right-pass']);
        $unknown = $this->postJson('/login', ['identifier' => 'ghost@example.com', 'password' => 'whatever1'])->assertUnprocessable()->json('errors.identifier.0');
        $wrong = $this->postJson('/login', ['identifier' => 'real@example.com', 'password' => 'wrong-pass1'])->assertUnprocessable()->json('errors.identifier.0');
        $this->assertSame($unknown, $wrong);

        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/login', ['identifier' => 'real@example.com', 'password' => 'wrong-pass1'])->assertUnprocessable();
        }
        // Five failures: even the right password is refused for now
        $this->postJson('/login', ['identifier' => 'real@example.com', 'password' => 'right-pass'])->assertUnprocessable();
        $this->assertGuest('member');
    }

    public function test_members_are_not_admins_and_guests_are_sent_to_the_public_login(): void
    {
        Member::query()->create(['email' => 'm@example.com', 'password' => 'secret-pass']);
        $this->postJson('/login', ['identifier' => 'm@example.com', 'password' => 'secret-pass'])->assertOk();
        $this->assertAuthenticated('member');
        // Signed in as a member, the admin is still closed
        $this->getJson('/api/admin/listings')->assertUnauthorized();
        $this->get('/admin')->assertRedirect('/admin/login');

        $this->post('/logout');
        $this->get('/account/saved')->assertRedirect('/login?redirect=%2Faccount%2Fsaved');
        $this->get('/login?redirect=/plot')->assertOk()->assertSee('name="redirect" value="/plot"', false);
        // An off-site redirect is ignored
        $this->get('/login?redirect=//evil.example')->assertOk()->assertSee('name="redirect" value="/"', false);
        $this->get('/register')->assertOk()->assertSee('data-auth-form="register"', false);
        $this->get('/forgot-password')->assertOk();
        $this->assertTrue(User::query()->count() === 0);
    }

    public function test_google_creates_links_and_never_duplicates(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        $current = null;
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturnUsing(function () use (&$current) {
            return $current;
        });
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
        $fake = function (string $id, string $email, string $name) use (&$current) {
            $current = (new SocialUser)->map(['id' => $id, 'name' => $name, 'email' => $email]);
        };

        // A new Google account
        $fake('g-1', 'New@Example.com', 'New Person');
        $this->get('/auth/google/callback')->assertRedirect('/');
        $this->assertDatabaseHas('members', ['email' => 'new@example.com', 'google_id' => 'g-1']);
        $this->post('/logout');

        // An existing email/password account with the same email is linked, not duplicated
        Member::query()->create(['email' => 'ann@example.com', 'name' => 'Ann', 'password' => 'secret-pass']);
        $fake('g-2', 'ann@example.com', 'Ann G');
        $this->get('/auth/google/callback')->assertRedirect('/');
        $this->assertSame(1, Member::query()->where('email', 'ann@example.com')->count());
        $this->assertDatabaseHas('members', ['email' => 'ann@example.com', 'google_id' => 'g-2', 'name' => 'Ann']);
        $this->assertTrue(Member::query()->where('google_id', 'g-2')->first()->hasPassword());
    }

    public function test_google_is_hidden_until_configured(): void
    {
        config(['services.google.client_id' => null]);
        $this->get('/login')->assertOk()->assertDontSee('/auth/google', false);
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 's']);
        $this->get('/login')->assertOk()->assertSee('/auth/google', false)->assertSee('Continue with Google');
    }

    public function test_password_reset_by_email_link_without_leaking_accounts(): void
    {
        Notification::fake();
        $member = Member::query()->create(['email' => 'reset@example.com', 'password' => 'old-pass-123']);

        $known = $this->postJson('/forgot-password', ['identifier' => 'reset@example.com'])->assertOk()->json('message');
        $unknown = $this->postJson('/forgot-password', ['identifier' => 'nobody@example.com'])->assertOk()->json('message');
        $this->assertSame($known, $unknown);
        $this->assertStringContainsString('If an account exists', $known);
        Notification::assertSentTo($member, MemberResetPassword::class);
        Notification::assertCount(1);

        $token = null;
        Notification::assertSentTo($member, MemberResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });
        $this->get("/reset-password/{$token}?email=reset@example.com")->assertOk()->assertSee('name="token"', false);
        $this->post('/reset-password', ['token' => $token, 'email' => 'reset@example.com', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])->assertRedirect('/login');
        $this->postJson('/login', ['identifier' => 'reset@example.com', 'password' => 'brand-new-pass'])->assertOk();
        // The link works once
        $this->post('/logout');
        $this->post('/reset-password', ['token' => $token, 'email' => 'reset@example.com', 'password' => 'another-pass-1', 'password_confirmation' => 'another-pass-1'])->assertSessionHasErrors('email');
    }

    public function test_password_reset_by_sms_code_for_a_mobile_only_account(): void
    {
        $sent = [];
        $this->app->bind(SmsSender::class, function () use (&$sent) {
            return new class($sent) implements SmsSender
            {
                public function __construct(public array &$sent) {}

                public function send(string $to, string $message): void
                {
                    $this->sent[] = [$to, $message];
                }
            };
        });
        Member::query()->create(['phone' => '+8801912345678', 'password' => 'old-pass-123']);

        $this->postJson('/forgot-password', ['identifier' => '01912345678'])->assertOk()->assertJsonPath('next', '/forgot-password/code?phone=01912345678');
        $this->assertCount(1, $sent);
        $this->assertSame('+8801912345678', $sent[0][0]);
        preg_match('/\b(\d{6})\b/', $sent[0][1], $m);

        // an unknown number gets the same answer and nothing is sent
        $this->postJson('/forgot-password', ['identifier' => '01612345678'])->assertOk()->assertJsonPath('next', '/forgot-password/code?phone=01612345678');
        $this->assertCount(1, $sent);

        $this->post('/forgot-password/code', ['phone' => '01912345678', 'code' => '000000', 'password' => 'new-pass-1234', 'password_confirmation' => 'new-pass-1234'])->assertSessionHasErrors('code');
        $this->post('/forgot-password/code', ['phone' => '01912345678', 'code' => $m[1], 'password' => 'new-pass-1234', 'password_confirmation' => 'new-pass-1234'])->assertRedirect('/login');
        $this->postJson('/login', ['identifier' => '01912345678', 'password' => 'new-pass-1234'])->assertOk();
        $this->assertSame(0, PhoneOtp::query()->count());
    }

    public function test_an_expired_sms_code_is_refused(): void
    {
        Member::query()->create(['phone' => '+8801912345678', 'password' => 'old-pass-123']);
        PhoneOtp::query()->create(['phone' => '+8801912345678', 'code_hash' => bcrypt('123456'), 'expires_at' => now()->subMinute()]);
        $this->post('/forgot-password/code', ['phone' => '01912345678', 'code' => '123456', 'password' => 'new-pass-1234', 'password_confirmation' => 'new-pass-1234'])->assertSessionHasErrors('code');
    }
}
