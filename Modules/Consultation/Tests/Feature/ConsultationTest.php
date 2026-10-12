<?php

namespace Modules\Consultation\Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Consultation\Availability;
use Modules\Consultation\Mail\ConsultationBooked;
use Modules\Consultation\Models\Consultation;
use Modules\Listing\Models\Listing;
use Tests\TestCase;

/** Booking a free consultation: the slot feed, the booking, its confirmation and the admin side. */
class ConsultationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DemoSeeder::class);
        // Monday 12 Oct 2026, 09:00 in Dhaka: with 3 hours' notice the first slot is 12:00.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-12 09:00', Availability::TIMEZONE));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function book(array $overrides = []): array
    {
        return $overrides + ['name' => 'Rahim Uddin', 'phone' => '01712 345678', 'mode' => 'phone', 'topic' => 'Plot', 'date' => '2026-10-13', 'time' => '14:00'];
    }

    public function test_feed_lists_working_days_with_slots_after_the_notice_period(): void
    {
        $days = $this->getJson('/consultation/availability')->assertOk()->json('days');

        $this->assertSame('2026-10-12', $days[0]['date']);
        $this->assertSame('12:00', $days[0]['slots'][0]['time']);
        $this->assertSame('12:00 PM', $days[0]['slots'][0]['label']);
        $this->assertSame('5:30 PM', end($days[0]['slots'])['label']);
        $this->assertNotContains('2026-10-16', array_column($days, 'date'), 'Friday is not a working day');
        $this->assertCount(12, $days);
        $this->assertSame('Tuesday, 13 October', $days[1]['label']);
    }

    public function test_feed_is_in_bangla_when_the_site_is(): void
    {
        $days = $this->getJson('/consultation/availability?lang=bn')->assertOk()->json('days');

        $this->assertSame('সোম', $days[0]['weekday']);
        $this->assertSame('12:00 PM', $days[0]['slots'][0]['label']);
    }

    public function test_booking_a_slot_takes_it_and_notifies_the_consultant(): void
    {
        Mail::fake();
        $listing = Listing::query()->first();

        $response = $this->postJson('/consultation', $this->book(['listing_id' => $listing->id, 'email' => 'rahim@example.com', 'message' => 'Budget 40 lakh']))
            ->assertCreated()
            ->assertJsonPath('consultation.date', 'Tuesday, 13 October 2026')
            ->assertJsonPath('consultation.time', '2:00 PM – 2:30 PM')
            ->assertJsonPath('consultation.mode', 'Phone call');
        $reference = $response->json('consultation.reference');
        $this->assertMatchesRegularExpression('/^RC-[A-Z2-9]{6}$/', $reference);

        $consultation = Consultation::query()->where('reference', $reference)->firstOrFail();
        $this->assertSame('2026-10-13 08:00:00', $consultation->scheduled_at->format('Y-m-d H:i:s'), 'stored in UTC');
        $this->assertSame('pending', $consultation->status);
        $this->assertSame('01712 345678', $consultation->phone);
        $this->assertSame($listing->id, $consultation->listing_id);
        $this->assertSame('https://wa.me/8801712345678', $consultation->visitorWhatsapp());
        Mail::assertSent(ConsultationBooked::class, fn (ConsultationBooked $mail) => $mail->hasTo('info@royalhousingbd.com') && $mail->consultation->is($consultation));

        // The slot is now taken: the feed flags it and a second booking is refused.
        $days = $this->getJson('/consultation/availability')->json('days');
        $slot = collect($days[1]['slots'])->firstWhere('time', '14:00');
        $this->assertTrue($slot['taken']);
        $this->postJson('/consultation', $this->book(['name' => 'Karim']))->assertUnprocessable()->assertJsonValidationErrors(['time']);
        $this->assertSame(1, Consultation::query()->count());

        // Cancelling frees it.
        $consultation->update(['status' => 'cancelled']);
        $this->postJson('/consultation', $this->book(['name' => 'Karim']))->assertCreated();
    }

    public function test_only_offered_slots_can_be_booked(): void
    {
        $this->postJson('/consultation', $this->book(['time' => '14:15']))->assertUnprocessable()->assertJsonValidationErrors(['time']);
        $this->postJson('/consultation', $this->book(['date' => '2026-10-16']))->assertUnprocessable()->assertJsonValidationErrors(['time']);
        $this->postJson('/consultation', $this->book(['date' => '2026-10-12', 'time' => '10:00']))->assertUnprocessable()->assertJsonValidationErrors(['time']);
        $this->postJson('/consultation', $this->book(['date' => '2026-11-30']))->assertUnprocessable()->assertJsonValidationErrors(['time']);
        $this->postJson('/consultation', $this->book(['mode' => 'video']))->assertUnprocessable()->assertJsonValidationErrors(['mode']);
        $this->postJson('/consultation', $this->book(['phone' => 'call me']))->assertUnprocessable()->assertJsonValidationErrors(['phone']);
        $this->postJson('/consultation', $this->book(['website' => 'http://spam']))->assertUnprocessable();
        $this->assertSame(0, Consultation::query()->count());
    }

    public function test_confirmation_page_and_calendar_file(): void
    {
        $reference = $this->postJson('/consultation', $this->book())->json('consultation.reference');

        $this->get("/consultation/{$reference}")
            ->assertOk()
            ->assertSee('Your consultation is booked')
            ->assertSee($reference)
            ->assertSee('Tuesday, 13 October 2026')
            ->assertSee("/consultation/{$reference}/calendar.ics")
            ->assertDontSee('01712 345678');

        $this->get("/consultation/{$reference}/calendar.ics")
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertSee('DTSTART:20261013T080000Z', false)
            ->assertSee('DTEND:20261013T083000Z', false);

        $this->get('/consultation/RC-ZZZZZZ')->assertNotFound();
    }

    public function test_launcher_is_on_every_page_until_bookings_are_paused(): void
    {
        $this->get('/')->assertOk()->assertSee('data-consult-open', false)->assertSee('Book a consultation');
        $this->get('/plot')->assertOk()->assertSee('data-consult-dialog', false);
        $this->get('/consultation')->assertOk()->assertSee('data-consult-inline', false)->assertDontSee('data-consult-open');
        $this->get('/consultation?lang=bn')->assertOk()->assertSee('ফ্রি পরামর্শ বুক করুন');

        Availability::save(['consult_enabled' => '0']);
        $this->get('/')->assertOk()->assertDontSee('data-consult-open');
        $this->get('/consultation')->assertOk()->assertDontSee('data-consult-widget')->assertSee('No consultation slots are open');
        $this->getJson('/consultation/availability')->assertOk()->assertJsonPath('days', []);
        $this->postJson('/consultation', $this->book())->assertNotFound();
    }

    public function test_admin_manages_bookings_and_availability(): void
    {
        $admin = User::factory()->create();
        $this->getJson('/api/admin/consultations')->assertUnauthorized();

        $reference = $this->postJson('/consultation', $this->book())->json('consultation.reference');
        $id = Consultation::query()->where('reference', $reference)->value('id');

        $this->actingAs($admin)->getJson('/api/admin/consultations?filter=pending')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('counts.pending', 1)
            ->assertJsonPath('counts.upcoming', 1)
            ->assertJsonPath('consultations.0.date', 'Tue 13 Oct 2026')
            ->assertJsonPath('consultations.0.time', '2:00 PM – 2:30 PM')
            ->assertJsonPath('consultations.0.whatsapp_url', 'https://wa.me/8801712345678');
        $this->actingAs($admin)->getJson('/api/admin/dashboard')->assertOk()->assertJsonPath('consultations.pending', 1);

        $this->actingAs($admin)->putJson("/api/admin/consultations/{$id}", ['status' => 'confirmed', 'admin_notes' => 'Called back'])
            ->assertOk()
            ->assertJsonPath('consultation.status', 'confirmed')
            ->assertJsonPath('counts.pending', 0);
        $this->actingAs($admin)->putJson("/api/admin/consultations/{$id}", ['status' => 'lost'])->assertUnprocessable();
        $this->actingAs($admin)->getJson('/api/admin/consultations?filter=cancelled')->assertJsonPath('total', 0);

        // Availability: Sat–Wed, hour-long slots from 11 to 15 with a day's notice; Thursday the 15th disappears.
        $this->actingAs($admin)->putJson('/api/admin/consultations/availability', ['consult_enabled' => '1', 'consult_days' => '6,7,1,2,3', 'consult_start' => '11:00', 'consult_end' => '15:00', 'consult_slot_minutes' => 60, 'consult_lead_hours' => 24, 'consult_horizon_days' => 7, 'consult_modes' => 'office,whatsapp,fax', 'consult_note' => ''])
            ->assertOk()
            ->assertJsonPath('availability.consult_modes', 'whatsapp,office')
            ->assertJsonPath('availability.consult_slot_minutes', '60');
        $this->actingAs($admin)->putJson('/api/admin/consultations/availability', ['consult_enabled' => '1', 'consult_days' => '6', 'consult_start' => '15:00', 'consult_end' => '11:00', 'consult_slot_minutes' => 60, 'consult_lead_hours' => 0, 'consult_horizon_days' => 7, 'consult_modes' => 'phone'])
            ->assertUnprocessable()->assertJsonValidationErrors(['consult_end']);

        $days = $this->getJson('/consultation/availability')->json('days');
        $this->assertSame(['2026-10-13', '2026-10-14', '2026-10-17', '2026-10-18'], array_column($days, 'date'));
        $this->assertSame(['11:00', '12:00', '13:00', '14:00'], array_column($days[0]['slots'], 'time'));
        $this->assertTrue(collect($days[0]['slots'])->firstWhere('time', '14:00')['taken'], 'the 14:00 booking still blocks its hour');
        $this->assertSame(['whatsapp', 'office'], $this->getJson('/consultation/availability')->json('modes'));

        $this->actingAs($admin)->deleteJson("/api/admin/consultations/{$id}")->assertOk()->assertJsonPath('counts.upcoming', 0);
        $this->assertSame(0, Consultation::query()->count());
    }
}
