<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Models\TickerItem;
use Modules\Content\Models\Video;
use Modules\Listing\Models\Listing;
use Modules\Listing\Services\ListingService;
use Modules\Masterplan\Services\MasterplanService;
use Modules\Settings\Settings;

/**
 * Sample content: one listing per type, a masterplan with a layout image and
 * plots, ticker items, a video and contact settings. Runs only while there
 * are no listings, so it never touches real data.
 */
class DemoSeeder extends Seeder
{
    public function run(ListingService $listings, MasterplanService $masterplans): void
    {
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
            ['type' => 'property', 'title' => 'Duplex house at Bashundhara R/A', 'location' => 'Bashundhara R/A, Dhaka', 'price' => '৳ 4.5 Crore', 'land_area' => '5 katha', 'built_area' => '4,200 sqft', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'facilities' => ['Car parking', 'Generator', 'Rooftop garden', '24/7 security'], 'description' => "A south-facing duplex on a corner plot with a private garden.\nReady to move in."],
            ['type' => 'plot', 'title' => 'Corner plot, Block C', 'location' => 'Purbachal, Dhaka', 'price' => '৳ 35 Lakh / katha', 'size' => '3 katha', 'facilities' => ['Boundary wall', 'Paved road', 'Electricity line', 'Gas line'], 'description' => 'Ready plot with all utilities, 200 m from the main road.'],
            ['type' => 'flat', 'title' => '1450 sqft flat with lake view', 'location' => 'Uttara Sector 7, Dhaka', 'call_for_price' => true, 'size' => '1450 sqft', 'bedrooms' => 3, 'baths' => 3, 'floor' => '5th of 9', 'facilities' => ['Lift', 'Generator', 'CCTV', 'Community hall'], 'description' => 'Three-bedroom flat with a lake-facing balcony and covered parking.'],
        ] as $attributes) {
            $ids[] = $listings->create($attributes + [
                'price' => '', 'call_for_price' => false, 'land_area' => '', 'built_area' => '', 'size' => '',
                'bedrooms' => 0, 'baths' => 0, 'floor' => '', 'video_url' => '', 'published' => true,
            ])->id;
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

        // A schematic SVG layout (one quarter of a real Dhaka project map) in
        // which every <g class="plan-plot"> is a plot; details for each are
        // filled in here the way the admin's builder would.
        $masterplan = $masterplans->create(
            ['name' => 'Royal City Phase 1', 'description' => 'Demo layout — replace it and the plot details in the admin.', 'published' => true],
            file_get_contents(__DIR__.'/assets/demo-layout.svg'),
        );

        $plots = [];
        foreach ($masterplan->layout_keys as $i => $key) {
            $plots[] = [
                'svg_key' => $key,
                'number' => 'P-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'size' => ['3 katha', '5 katha', '2.5 katha'][$i % 3],
                'price' => $i % 4 === 3 ? '' : '৳ 35 Lakh / katha',
                'position' => 'Facing '.['north', 'east', 'south', 'west'][$i % 4],
                'status' => ['available', 'available', 'booked', 'available', 'sold'][$i % 5],
            ];
        }
        $masterplans->savePlots($masterplan, $plots);

        // The plot listing is a development on this map, so its card shows availability.
        Listing::query()->where('type', 'plot')->update(['masterplan_id' => $masterplan->id]);
    }
}
