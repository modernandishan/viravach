<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class HomePageSeeder extends Seeder
{
    public function run(): void
    {
        Page::updateOrCreate(
            ['slug' => '/'],
            [
                'title' => [
                    'en' => 'Viravach',
                    'fa' => 'ویراواچ',
                    'ar' => 'ويراواتش',
                    'ru' => 'Виравач',
                    'tr' => 'Viravatç',
                ],
                'is_active'    => true,
                'published_at' => now(),
                'sort_order'   => 0,
            ]
        );
    }
}
