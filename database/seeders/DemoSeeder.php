<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
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

        // A generated layout image (1600×1000) with 16 plots over its blocks.
        $path = 'uploads/masterplans/demo-layout.png';
        Storage::disk('public')->put($path, file_get_contents(__DIR__.'/assets/demo-layout.png'));
        $masterplan = $masterplans->create(['name' => 'Royal City Phase 1', 'description' => 'Demo layout — replace the image and plots in the admin map builder.', 'published' => true]);
        $masterplan->update(['image_path' => $path, 'image_width' => 1600, 'image_height' => 1000]);

        $plots = [];
        $number = 1;
        foreach ([[80, 80, 700, 400], [900, 80, 1520, 400], [80, 600, 700, 920], [900, 600, 1520, 920]] as [$x0, $y0, $x1, $y1]) {
            $cellW = ($x1 - $x0) / 4;
            for ($c = 0; $c < 4; $c++) {
                $left = $x0 + $c * $cellW + 6;
                $right = $x0 + ($c + 1) * $cellW - 6;
                $plots[] = [
                    'number' => 'A'.$number,
                    'size' => '3 katha',
                    'price' => '৳ 35 Lakh / katha',
                    'position' => 'Block A, facing '.['north', 'east', 'south', 'west'][$c],
                    'status' => ['available', 'booked', 'sold'][$number % 3],
                    'polygon' => [['x' => $left, 'y' => $y0], ['x' => $right, 'y' => $y0], ['x' => $right, 'y' => $y1], ['x' => $left, 'y' => $y1]],
                ];
                $number++;
            }
        }
        $masterplans->savePlots($masterplan, $plots);

        // The plot listing is a development on this map, so its card shows availability.
        Listing::query()->where('type', 'plot')->update(['masterplan_id' => $masterplan->id]);
    }
}
