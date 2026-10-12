<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Content\Models\TickerItem;
use Modules\Content\Models\Video;
use Modules\Listing\Database\Seeders\FeatureSeeder;
use Modules\Listing\Database\Seeders\ListingTypeSeeder;
use Modules\Listing\Database\Seeders\LocationSeeder;
use Modules\Listing\Models\Feature;
use Modules\Listing\Models\Listing;
use Modules\Listing\Models\Location;
use Modules\Listing\Services\ListingService;
use Modules\Masterplan\Services\MasterplanService;
use Modules\Settings\Settings;

/**
 * Sample content: the standard types, features and locations, one listing per type, four masterplans with layout images and
 * plots, ticker items, a video and contact settings. Runs only while there
 * are no listings, so it never touches real data.
 */
class DemoSeeder extends Seeder
{
    public function run(ListingService $listings, MasterplanService $masterplans): void
    {
        $this->call([ListingTypeSeeder::class, FeatureSeeder::class, LocationSeeder::class]);
        if (Listing::query()->exists()) {
            return;
        }

        Settings::save([
            'whatsapp_number' => '8801700000000',
            'phone' => '+880 1700-000000',
            'email' => 'info@royalhousingbd.com',
            'address' => 'House 12, Road 5, Bashundhara R/A, Dhaka 1229',
            'working_hours' => 'Saturday - Thursday: 9:00 AM - 6:00 PM',
            'team_members' => json_encode([
                ['name' => 'Tanjim Islam', 'role' => 'Founder & Chairman', 'photo' => '', 'leader' => true],
                ['name' => 'Managing Director', 'role' => 'Managing Director', 'photo' => '', 'leader' => true],
                ['name' => 'Sales Manager', 'role' => 'Sales', 'photo' => '', 'leader' => false],
            ]),
            'about_text' => 'Royal Housing BD develops residential plots and flats around Dhaka. Browse our listings, explore the project masterplan, and message us on WhatsApp to arrange a visit.',
            'facebook_url' => 'https://facebook.com/',
            'youtube_url' => 'https://youtube.com/',
            'meta_title' => 'Royal Housing BD — Property, Plots & Flats in Dhaka',
            'meta_description' => 'Browse property, plots and flats from Royal Housing BD. Explore the interactive project masterplan and contact us on WhatsApp.',
        ]);

        $ids = [];
        foreach ([
            ['type' => 'property', 'title' => 'Duplex house in Uttara', 'location' => 'Uttara', 'price' => '৳ 4.5 Crore', 'land_area' => '5 katha', 'built_area' => '4,200 sqft', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'features' => ['Parking', 'Generator', 'Rooftop garden', '24/7 security'], 'description' => "A south-facing duplex on a corner plot with a private garden.\nReady to move in."],
            ['type' => 'plot', 'title' => 'Corner plot, Block C', 'location' => 'Hasara (Munshiganj)', 'price' => '৳ 35 Lakh / katha', 'size' => '3 katha', 'features' => ['Boundary wall', 'Paved road', 'Electricity line', 'Gas connection'], 'description' => 'Ready plot with all utilities, 200 m from the main road.'],
            ['type' => 'flat', 'title' => '1450 sqft flat with lake view', 'location' => 'Mirpur', 'call_for_price' => true, 'size' => '1450 sqft', 'bedrooms' => 3, 'baths' => 3, 'floor' => '5th of 9', 'features' => ['Lift', 'Generator', 'CCTV', 'Community hall'], 'description' => 'Three-bedroom flat with a lake-facing balcony and covered parking.'],
            ['type' => 'resort', 'title' => 'Hilltop eco resort share', 'location' => 'Cox\'s Bazar', 'price' => '৳ 25 Lakh / share', 'land_area' => '2 acres', 'built_area' => '12 cottages', 'features' => ['Swimming pool', 'Generator', '24/7 security', 'Parking'], 'description' => 'Co-own a cottage in a managed resort and earn from bookings.'],
            ['type' => 'five-star-hotel', 'title' => 'Beachfront hotel suite, Cox\'s Bazar', 'location' => 'Cox\'s Bazar', 'call_for_price' => true, 'size' => '850 sqft', 'bedrooms' => 1, 'baths' => 1, 'floor' => '9th of 14', 'features' => ['Lift', 'Swimming pool', 'Gym', 'Parking', 'CCTV'], 'description' => 'A sea-facing suite in a managed five star hotel with rental returns.'],
        ] as $attributes) {
            $features = Feature::query()->whereIn('name', $attributes['features'])->pluck('id')->all();
            foreach (array_diff($attributes['features'], Feature::query()->whereIn('name', $attributes['features'])->pluck('name')->all()) as $name) {
                $features[] = Feature::query()->create(['name' => $name, 'slug' => Str::slug($name)])->id;
            }
            unset($attributes['features']);
            $attributes['location_id'] = Location::query()->where('name', $attributes['location'])->value('id');
            $ids[] = $listings->create($attributes + [
                'price' => '', 'call_for_price' => false, 'land_area' => '', 'built_area' => '', 'size' => '',
                'bedrooms' => 0, 'baths' => 0, 'floor' => '', 'video_url' => '', 'published' => true,
            ], $features)->id;
        }
        $listings->setFeatured($ids);

        foreach ([
            ['text' => 'New plots released in Block C — book your site visit today', 'link' => '/plot'],
            ['text' => 'Eid offer: 5% discount on full payment for flats', 'link' => '/flat'],
            ['text' => 'Office closed on Friday', 'link' => ''],
        ] as $i => $item) {
            TickerItem::query()->create($item + ['active' => true, 'sort_order' => $i]);
        }
        Video::query()->create(['title' => 'Project walkthrough', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'sort_order' => 0]);

        // Four schematic SVG layouts, one per quadrant of a real Dhaka project
        // map, in which every <g class="plan-plot"> is a plot; details for each
        // are filled in here the way the admin's builder would.
        $quadrants = [
            'nw' => 'Royal City Phase 1 — North West',
            'ne' => 'Royal City Phase 1 — North East',
            'sw' => 'Royal City Phase 1 — South West',
            'se' => 'Royal City Phase 1 — South East',
        ];
        $first = null;
        foreach ($quadrants as $file => $name) {
            $masterplan = $masterplans->create(
                ['name' => $name, 'description' => 'Demo layout — replace it and the plot details in the admin.', 'published' => true],
                file_get_contents(__DIR__."/assets/layouts/{$file}.svg"),
            );
            $first ??= $masterplan;

            $plots = [];
            foreach ($masterplan->layout_keys as $i => $key) {
                $plots[] = [
                    'svg_key' => $key,
                    'number' => strtoupper($key),
                    'size' => ['3 katha', '5 katha', '2.5 katha'][$i % 3],
                    'price' => $i % 4 === 3 ? '' : '৳ 35 Lakh / katha',
                    'position' => 'Facing '.['north', 'east', 'south', 'west'][$i % 4],
                    'status' => ['available', 'available', 'booked', 'available', 'sold'][$i % 5],
                ];
            }
            $masterplans->savePlots($masterplan, $plots);
        }

        // The plot listing is a development on this map, so its card shows availability.
        Listing::query()->where('type', 'plot')->update(['masterplan_id' => $first->id]);
    }
}
