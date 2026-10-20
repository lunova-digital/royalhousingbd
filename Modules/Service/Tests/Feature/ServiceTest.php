<?php

namespace Modules\Service\Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Service\Database\Seeders\ServiceSeeder;
use Modules\Service\Models\Service;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_lists_active_services_and_pages_carry_both_ctas(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(ServiceSeeder::class);

        $this->get('/')->assertOk()->assertSee('Other services')->assertSee('/services/car', false)->assertSee('/services/solar', false)->assertSee('/services/lift', false);
        // Not in the nav, the hero or the listing types
        $this->get('/projects')->assertOk()->assertDontSee('href="/services/car" class="nav-link"', false);

        $this->get('/services/solar')->assertOk()
            ->assertSee('Solar')
            ->assertSee('interested%20in%20your%20Solar%20service', false)
            ->assertSee('data-consult-note="Service: Solar"', false)
            ->assertSee('wa.me/8801700000000', false);
        $this->get('/services/solar?lang=bn')->assertOk()->assertSee('সোলার')->assertSee('অন্যান্য সেবা');
    }

    public function test_inactive_services_are_hidden_everywhere(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(ServiceSeeder::class);
        Service::query()->where('slug', 'lift')->update(['active' => false]);

        $this->get('/')->assertDontSee('/services/lift', false);
        $this->get('/services/lift')->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('/services/lift')->assertSee('/services/car');
    }

    public function test_admin_manages_services_with_both_languages(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(ServiceSeeder::class);
        $this->actingAs(User::factory()->create());

        $items = Service::query()->ordered()->get()->map->only(['slug', 'title', 'title_bn', 'summary', 'summary_bn', 'description', 'description_bn', 'benefits', 'benefits_bn', 'image', 'gallery', 'active'])->all();
        $items[] = ['slug' => '', 'title' => 'Interior design', 'title_bn' => 'ইন্টেরিয়র', 'benefits' => ['One', ' ', 'Two'], 'active' => true];
        $items[0]['active'] = false;
        $this->putJson('/api/admin/services', ['items' => $items])->assertOk()->assertJsonPath('items.3.slug', 'interior-design')->assertJsonPath('items.3.benefits', ['One', 'Two']);

        $this->assertFalse(Service::query()->where('slug', 'car')->value('active'));
        // The list is the whole truth: leaving one out deletes it
        $this->putJson('/api/admin/services', ['items' => array_slice($items, 1, 2)])->assertOk();
        $this->assertSame(['solar', 'lift'], Service::query()->ordered()->pluck('slug')->all());
        $this->putJson('/api/admin/services', ['items' => [['title' => '']]])->assertUnprocessable();
    }
}
