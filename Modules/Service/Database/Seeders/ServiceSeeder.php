<?php

namespace Modules\Service\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Service\Models\Service;

/** Car, Solar and Lift, to be edited in the admin (Site → Other services). Safe to re-run: existing services are left alone. */
class ServiceSeeder extends Seeder
{
    public const SERVICES = [
        'car' => [
            'title' => 'Car', 'title_bn' => 'গাড়ি',
            'summary' => 'Premium new and reconditioned cars, with documents handled for you.',
            'summary_bn' => 'নতুন ও রিকন্ডিশন্ড প্রিমিয়াম গাড়ি, কাগজপত্রের ঝামেলা আমাদের।',
            'description' => "Alongside property, we help families and businesses buy a car with the same care: verified papers, fair pricing and an advisor who stays with you until the keys are in your hand.\n\nTell us your budget and what you need the car for, and we will shortlist options and arrange a viewing.",
            'description_bn' => "সম্পত্তির পাশাপাশি আমরা একই যত্নে গাড়ি কিনতেও পরিবার ও প্রতিষ্ঠানকে সাহায্য করি: যাচাইকৃত কাগজপত্র, ন্যায্য দাম আর চাবি হাতে পাওয়া পর্যন্ত একজন পরামর্শক পাশে থাকেন।\n\nআপনার বাজেট ও প্রয়োজন জানান, আমরা কয়েকটি অপশন বেছে দেখার ব্যবস্থা করব।",
            'benefits' => ['Verified papers and transparent history', 'Fair, upfront pricing', 'Finance and registration support', 'Test drive and viewing arranged'],
            'benefits_bn' => ['যাচাইকৃত কাগজপত্র ও স্বচ্ছ ইতিহাস', 'ন্যায্য ও আগে থেকে জানা দাম', 'ফাইন্যান্স ও রেজিস্ট্রেশনে সহায়তা', 'টেস্ট ড্রাইভ ও দেখার ব্যবস্থা'],
        ],
        'solar' => [
            'title' => 'Solar', 'title_bn' => 'সোলার',
            'summary' => 'Rooftop solar for homes, flats and buildings: lower bills, clean power.',
            'summary_bn' => 'বাসা, ফ্ল্যাট ও ভবনের জন্য রুফটপ সোলার: কম বিল, পরিচ্ছন্ন বিদ্যুৎ।',
            'description' => "Cut your electricity bill and keep the lights on. We survey your roof, design a system that fits your usage, install it and look after it afterwards.\n\nIdeal for new homes in our projects as well as existing buildings.",
            'description_bn' => "বিদ্যুৎ বিল কমান আর বিদ্যুৎ চলে গেলেও আলো জ্বালিয়ে রাখুন। আমরা ছাদ দেখে আপনার ব্যবহারের উপযোগী সিস্টেম ডিজাইন করি, বসিয়ে দিই এবং পরেও দেখভাল করি।\n\nআমাদের প্রজেক্টের নতুন বাসা ও বিদ্যমান ভবন, দুটোর জন্যই উপযুক্ত।",
            'benefits' => ['Free roof survey and sizing', 'Lower monthly electricity bills', 'Installation and after-sales service', 'Works with new and existing buildings'],
            'benefits_bn' => ['বিনামূল্যে ছাদ পরিদর্শন ও সাইজিং', 'মাসিক বিদ্যুৎ বিল কম', 'ইনস্টলেশন ও বিক্রয়োত্তর সেবা', 'নতুন ও পুরোনো ভবন, দুটোতেই কাজ করে'],
        ],
        'lift' => [
            'title' => 'Lift', 'title_bn' => 'লিফট',
            'summary' => 'Safe, quiet passenger lifts for residential and commercial buildings.',
            'summary_bn' => 'আবাসিক ও বাণিজ্যিক ভবনের জন্য নিরাপদ, নীরব যাত্রীবাহী লিফট।',
            'description' => "From a six-storey flat building to a hotel, we supply, install and service passenger lifts that are safe, quiet and built to last.\n\nShare the number of floors and expected traffic and we will recommend the right capacity and finish.",
            'description_bn' => "ছয়তলা ফ্ল্যাট ভবন থেকে হোটেল পর্যন্ত, আমরা নিরাপদ, নীরব ও টেকসই যাত্রীবাহী লিফট সরবরাহ, স্থাপন ও সার্ভিস করি।\n\nকত তলা আর কতজন ব্যবহার করবেন জানান, আমরা সঠিক ধারণক্ষমতা ও ফিনিশ সুপারিশ করব।",
            'benefits' => ['Safety-certified equipment', 'Quiet, smooth ride', 'Installation and yearly maintenance', 'Custom cabin finishes'],
            'benefits_bn' => ['নিরাপত্তা-সনদপ্রাপ্ত যন্ত্রাংশ', 'নীরব ও মসৃণ যাত্রা', 'ইনস্টলেশন ও বার্ষিক রক্ষণাবেক্ষণ', 'পছন্দমতো কেবিন ফিনিশ'],
        ],
    ];

    public function run(): void
    {
        $order = (int) Service::query()->max('sort_order');
        foreach (self::SERVICES as $slug => $data) {
            $files = glob(public_path("images/services/{$slug}-*.webp")) ?: [];
            $hero = is_file(public_path("images/services/{$slug}-hero.webp")) ? "/images/services/{$slug}-hero.webp" : '';
            $gallery = array_values(array_map(fn ($f) => '/images/services/'.basename($f), array_filter($files, fn ($f) => ! str_contains($f, '-hero'))));
            Service::query()->firstOrCreate(['slug' => $slug], $data + ['image' => $hero, 'gallery' => $gallery, 'active' => true, 'sort_order' => ++$order]);
        }
    }
}
