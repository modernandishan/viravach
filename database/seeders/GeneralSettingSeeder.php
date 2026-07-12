<?php

namespace Database\Seeders;

use App\Models\GeneralSetting;
use Illuminate\Database\Seeder;

class GeneralSettingSeeder extends Seeder
{
    public function run(): void
    {
        $setting = GeneralSetting::current();

        $setting->update([
            'site_name' => [
                'fa' => 'ویراواچ',
                'en' => 'Viravach',
                'ar' => 'ویراواچ',
                'ru' => 'Виравач',
                'tr' => 'Viravaç',
            ],
            'site_tagline' => [
                'fa' => 'تجارت بی‌مرز',
                'en' => 'Trade Without Borders',
                'ar' => 'تجارة بلا حدود',
                'ru' => 'Торговля без границ',
                'tr' => 'Sınırsız Ticaret',
            ],
        ]);
    }
}
