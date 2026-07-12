<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Country::updateOrCreate(
            ['iso2' => 'IR'],
            [
                'name' => [
                    'en' => 'Iran',
                    'fa' => 'ایران',
                    'tr' => 'İran',
                    'ru' => 'Иран',
                    'ar' => 'إيران',
                ],
                'slug' => 'iran',
                'official_name' => [
                    'en' => 'Islamic Republic of Iran',
                    'fa' => 'جمهوری اسلامی ایران',
                    'tr' => 'İran İslam Cumhuriyeti',
                    'ru' => 'Исламская Республика Иран',
                    'ar' => 'جمهورية إيران الإسلامية',
                ],
                'iso2'         => 'IR',
                'iso3'         => 'IRN',
                'numeric_code' => '364',
                'phone_code'   => '98',
                'capital' => [
                    'en' => 'Tehran',
                    'fa' => 'تهران',
                    'tr' => 'Tahran',
                    'ru' => 'Тегеран',
                    'ar' => 'طهران',
                ],
                'currency'         => 'IRR',
                'currency_symbol'  => '﷼',
                'currency_name' => [
                    'en' => 'Iranian Rial',
                    'fa' => 'ریال ایران',
                    'tr' => 'İran Riyali',
                    'ru' => 'Иранский риал',
                    'ar' => 'ريال إيراني',
                ],
                'tld'        => '.ir',
                'region'     => 'Asia',
                'subregion'  => 'Southern Asia',
                'latitude'   => 32.427908,
                'longitude'  => 53.688046,
                'bounding_box' => [
                    'northeast' => ['lat' => 39.782036, 'lng' => 63.317470],
                    'southwest' => ['lat' => 25.064093, 'lng' => 44.043045],
                ],
                'area'           => 1648195,
                'population'     => 89800000,
                'flag_emoji'     => '🇮🇷',
                'is_active'      => true,
            ]
        );
    }
}
