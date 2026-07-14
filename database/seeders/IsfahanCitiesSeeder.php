<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\State;
use Illuminate\Database\Seeder;

class IsfahanCitiesSeeder extends Seeder
{
    protected array $cities = [
        [
            'name' => [
                'fa' => 'اصفهان',
                'en' => 'Isfahan',
                'ar' => 'أصفهان',
                'ru' => 'Исфахан',
                'tr' => 'İsfahan',
            ],
            'slug' => 'isfahan',
            'code' => '1316',
            'latitude' => 32.6546,
            'longitude' => 51.6680,
        ],
        [
            'name' => [
                'fa' => 'کاشان',
                'en' => 'Kashan',
                'ar' => 'كاشان',
                'ru' => 'Кашан',
                'tr' => 'Kaşan',
            ],
            'slug' => 'kashan',
            'code' => '1304',
            'latitude' => 33.9833,
            'longitude' => 51.4333,
        ],
        [
            'name' => [
                'fa' => 'خمینی‌شهر',
                'en' => 'Khomeyni Shahr',
                'ar' => 'خميني شهر',
                'ru' => 'Хомейни-Шахр',
                'tr' => 'Humeyni Şehr',
            ],
            'slug' => 'khomeyni-shahr',
            'code' => '1359',
            'latitude' => 32.6833,
            'longitude' => 51.5333,
        ],
        [
            'name' => [
                'fa' => 'نجف‌آباد',
                'en' => 'Najafabad',
                'ar' => 'نجد آباد',
                'ru' => 'Наджафабад',
                'tr' => 'Necafabad',
            ],
            'slug' => 'najafabad',
            'code' => '1337',
            'latitude' => 32.6333,
            'longitude' => 51.3667,
        ],
        [
            'name' => [
                'fa' => 'لنجان',
                'en' => 'Lenjan',
                'ar' => 'لنجان',
                'ru' => 'Ленджан',
                'tr' => 'Lencan',
            ],
            'slug' => 'lenjan',
            'code' => '1352',
            'latitude' => 32.5167,
            'longitude' => 51.4167,
        ],
        [
            'name' => [
                'fa' => 'فلاورجان',
                'en' => 'Falavarjan',
                'ar' => 'فلاورجان',
                'ru' => 'Фалаварджан',
                'tr' => 'Felaverican',
            ],
            'slug' => 'falavarjan',
            'code' => '1360',
            'latitude' => 32.5667,
            'longitude' => 51.5833,
        ],
        [
            'name' => [
                'fa' => 'شاهین‌شهر',
                'en' => 'Shahin Shahr',
                'ar' => 'شاهين شهر',
                'ru' => 'Шахин-Шахр',
                'tr' => 'Şahin Şehr',
            ],
            'slug' => 'shahin-shahr',
            'code' => '1368',
            'latitude' => 32.8167,
            'longitude' => 51.6000,
        ],
        [
            'name' => [
                'fa' => 'شهرضا',
                'en' => 'Shahreza',
                'ar' => 'شهرضا',
                'ru' => 'Шахреза',
                'tr' => 'Şehreza',
            ],
            'slug' => 'shahreza',
            'code' => '1302',
            'latitude' => 32.0333,
            'longitude' => 51.8667,
        ],
        [
            'name' => [
                'fa' => 'مبارکه',
                'en' => 'Mobarakeh',
                'ar' => 'مباركه',
                'ru' => 'Мобараке',
                'tr' => 'Mübareke',
            ],
            'slug' => 'mobarakeh',
            'code' => '1331',
            'latitude' => 32.3833,
            'longitude' => 51.6333,
        ],
        [
            'name' => [
                'fa' => 'برخوار',
                'en' => 'Borkhar',
                'ar' => 'برخوار',
                'ru' => 'Борхар',
                'tr' => 'Borhvar',
            ],
            'slug' => 'borkhar',
            'code' => '1385',
            'latitude' => 32.8000,
            'longitude' => 51.7167,
        ],
        [
            'name' => [
                'fa' => 'آران و بیدگل',
                'en' => 'Aran va Bidgol',
                'ar' => 'آران و بيدگل',
                'ru' => 'Аран ва Бидголь',
                'tr' => 'Aran ve Bidgol',
            ],
            'slug' => 'aran-va-bidgol',
            'code' => '1375',
            'latitude' => 33.9500,
            'longitude' => 51.3833,
        ],
        [
            'name' => [
                'fa' => 'سمیرم',
                'en' => 'Semirom',
                'ar' => 'سميرم',
                'ru' => 'Семиром',
                'tr' => 'Semiram',
            ],
            'slug' => 'semirom',
            'code' => '1342',
            'latitude' => 31.4333,
            'longitude' => 51.6500,
        ],
        [
            'name' => [
                'fa' => 'گلپایگان',
                'en' => 'Golpayegan',
                'ar' => 'گلبايگان',
                'ru' => 'Голпайеган',
                'tr' => 'Gelpayegan',
            ],
            'slug' => 'golpayegan',
            'code' => '1318',
            'latitude' => 33.4500,
            'longitude' => 50.2833,
        ],
        [
            'name' => [
                'fa' => 'تیران و کرون',
                'en' => 'Tiran va Karon',
                'ar' => 'تيران و كرون',
                'ru' => 'Тиран ва Карон',
                'tr' => 'Tiran ve Karon',
            ],
            'slug' => 'tiran-va-karon',
            'code' => '1376',
            'latitude' => 32.5333,
            'longitude' => 51.2667,
        ],
        [
            'name' => [
                'fa' => 'فریدن',
                'en' => 'Faridan',
                'ar' => 'فريدن',
                'ru' => 'Фаридан',
                'tr' => 'Feriden',
            ],
            'slug' => 'faridan',
            'code' => '1328',
            'latitude' => 33.2833,
            'longitude' => 50.2167,
        ],
        [
            'name' => [
                'fa' => 'نطنز',
                'en' => 'Natanz',
                'ar' => 'نطنز',
                'ru' => 'Натанз',
                'tr' => 'Natenz',
            ],
            'slug' => 'natanz',
            'code' => '1336',
            'latitude' => 33.5833,
            'longitude' => 51.9167,
        ],
        [
            'name' => [
                'fa' => 'اردستان',
                'en' => 'Ardestan',
                'ar' => 'اردستان',
                'ru' => 'Ардастан',
                'tr' => 'Erdestan',
            ],
            'slug' => 'ardestan',
            'code' => '1327',
            'latitude' => 33.3833,
            'longitude' => 52.3667,
        ],
        [
            'name' => [
                'fa' => 'نائین',
                'en' => 'Nain',
                'ar' => 'نائين',
                'ru' => 'Наин',
                'tr' => 'Nain',
            ],
            'slug' => 'nain',
            'code' => '1326',
            'latitude' => 32.8833,
            'longitude' => 53.0833,
        ],
        [
            'name' => [
                'fa' => 'جرقویه',
                'en' => 'Jarqavieh',
                'ar' => 'جرقويه',
                'ru' => 'Джаркавие',
                'tr' => 'Cerkaviye',
            ],
            'slug' => 'jarqavieh',
            'code' => '1400',
            'latitude' => 32.7167,
            'longitude' => 51.9167,
        ],
        [
            'name' => [
                'fa' => 'فریدون‌شهر',
                'en' => 'Fereydun Shahr',
                'ar' => 'فريدون شهر',
                'ru' => 'Ферейдун-Шахр',
                'tr' => 'Fereydun Şehr',
            ],
            'slug' => 'fereydun-shahr',
            'code' => '1358',
            'latitude' => 33.0667,
            'longitude' => 50.1000,
        ],
        [
            'name' => [
                'fa' => 'دهاقان',
                'en' => 'Dehaqan',
                'ar' => 'دهاقان',
                'ru' => 'Дехакан',
                'tr' => 'Dekan',
            ],
            'slug' => 'dehaqan',
            'code' => '1382',
            'latitude' => 32.2000,
            'longitude' => 51.5000,
        ],
        [
            'name' => [
                'fa' => 'خوانسار',
                'en' => 'Khvansar',
                'ar' => 'خوانسار',
                'ru' => 'Хвансар',
                'tr' => 'Hvansar',
            ],
            'slug' => 'khvansar',
            'code' => '1320',
            'latitude' => 33.2167,
            'longitude' => 50.7333,
        ],
        [
            'name' => [
                'fa' => 'چادگان',
                'en' => 'Chadegan',
                'ar' => 'چادگان',
                'ru' => 'Чадеган',
                'tr' => 'Çadegan',
            ],
            'slug' => 'chadegan',
            'code' => '1381',
            'latitude' => 33.3167,
            'longitude' => 50.7167,
        ],
        [
            'name' => [
                'fa' => 'ورزنه',
                'en' => 'Varzaneh',
                'ar' => 'ورزنه',
                'ru' => 'Варзане',
                'tr' => 'Verzene',
            ],
            'slug' => 'varzaneh',
            'code' => '1383',
            'latitude' => 32.8500,
            'longitude' => 52.3833,
        ],
        [
            'name' => [
                'fa' => 'بوئین و میاندشت',
                'en' => 'Buin va Miandasht',
                'ar' => 'بگوئين و مياندشت',
                'ru' => 'Буин ва Миандашт',
                'tr' => 'Buin ve Miandaşt',
            ],
            'slug' => 'buin-va-miandasht',
            'code' => '1392',
            'latitude' => 33.0167,
            'longitude' => 50.4833,
        ],
        [
            'name' => [
                'fa' => 'کوهپایه',
                'en' => 'Kuhpayeh',
                'ar' => 'كوهبايه',
                'ru' => 'Кухпайе',
                'tr' => 'Kuhpaye',
            ],
            'slug' => 'kuhpayeh',
            'code' => '1386',
            'latitude' => 32.6500,
            'longitude' => 52.1833,
        ],
        [
            'name' => [
                'fa' => 'میمه و وزوان',
                'en' => 'Meymeh va Vazvan',
                'ar' => 'ميمه و وزوان',
                'ru' => 'Мейме ва Вазван',
                'tr' => 'Meyme ve Vazvan',
            ],
            'slug' => 'meymeh-va-vazvan',
            'code' => '1403',
            'latitude' => 33.2167,
            'longitude' => 51.3167,
        ],
        [
            'name' => [
                'fa' => 'هرند',
                'en' => 'Harand',
                'ar' => 'هرند',
                'ru' => 'Харанд',
                'tr' => 'Herend',
            ],
            'slug' => 'harand',
            'code' => '1388',
            'latitude' => 32.4167,
            'longitude' => 52.0500,
        ],
        [
            'name' => [
                'fa' => 'خور و بیابانک',
                'en' => 'Khor va Biyabanak',
                'ar' => 'خور و بيابانك',
                'ru' => 'Хор ва Биябанак',
                'tr' => 'Hor ve Biyabanak',
            ],
            'slug' => 'khor-va-biyabanak',
            'code' => '1382',
            'latitude' => 33.5500,
            'longitude' => 54.2167,
        ],
    ];

    public function run(): void
    {
        $state = State::where('code', '04')->first();

        if (! $state) {
            $this->command->error('استان اصفهان با کد 04 یافت نشد!');

            return;
        }

        foreach ($this->cities as $cityData) {
            City::updateOrCreate(
                ['slug' => $cityData['slug']],
                [
                    'state_id' => $state->id,
                    'name' => $cityData['name'],
                    'latitude' => $cityData['latitude'],
                    'longitude' => $cityData['longitude'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('تمامی 29 شهرستان استان اصفهان با موفقیت ایجاد/بروزرسانی شدند.');
    }
}
