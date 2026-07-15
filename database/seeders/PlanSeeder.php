<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Laravelcm\Subscriptions\Interval;

class PlanSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedFreePlan();

        // Pro: پایه‌ی ماهانه ۲۹ دلار → تخفیف پلکانی (۳ماهه ~۱۰٪، ۶ماهه ~۱۵٪، سالانه ~۲۵٪)
        // trial یک‌ماهه فقط برای پلن‌های Pro؛ یکتا بودن آن در لایه‌ی اپلیکیشن کنترل می‌شود.
        $this->seedPaidPlan(
            slugPrefix: 'pro',
            names: [
                'en' => 'Pro',
                'fa' => 'حرفه‌ای',
                'ar' => 'احترافي',
                'ru' => 'Про',
                'tr' => 'Pro',
            ],
            descriptions: [
                'en' => 'Advanced tools for growing companies, with a one-month free trial.',
                'fa' => 'ابزارهای پیشرفته برای شرکت‌های در حال رشد، به‌همراه یک ماه دوره آزمایشی رایگان.',
                'ar' => 'أدوات متقدمة للشركات النامية، مع شهر تجريبي مجاني.',
                'ru' => 'Расширенные инструменты для растущих компаний с месяцем бесплатного пробного периода.',
                'tr' => 'Büyüyen şirketler için gelişmiş araçlar, bir aylık ücretsiz deneme ile.',
            ],
            prices: ['quarterly' => 79, 'semiannual' => 149, 'yearly' => 259],
            trialDays: 30,
            sortOrderStart: 1,
        );

        // Pro Plus: پایه‌ی ماهانه ۷۹ دلار → همان تخفیف پلکانی، بدون trial
        $this->seedPaidPlan(
            slugPrefix: 'pro-plus',
            names: [
                'en' => 'Pro Plus',
                'fa' => 'حرفه‌ای پلاس',
                'ar' => 'احترافي بلس',
                'ru' => 'Про Плюс',
                'tr' => 'Pro Plus',
            ],
            descriptions: [
                'en' => 'The complete package with our highest tier of features and support.',
                'fa' => 'بسته کامل با بالاترین سطح امکانات و پشتیبانی.',
                'ar' => 'الباقة الكاملة مع أعلى مستوى من الميزات والدعم.',
                'ru' => 'Полный пакет с максимальным уровнем функций и поддержки.',
                'tr' => 'En üst düzey özellikler ve destekle eksiksiz paket.',
            ],
            prices: ['quarterly' => 219, 'semiannual' => 399, 'yearly' => 699],
            trialDays: 0,
            sortOrderStart: 4,
        );
    }

    private function seedFreePlan(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => [
                    'en' => 'Free',
                    'fa' => 'رایگان',
                    'ar' => 'مجاني',
                    'ru' => 'Бесплатный',
                    'tr' => 'Ücretsiz',
                ],
                'description' => [
                    'en' => 'Get started at no cost with the essential features every company needs. No time limit.',
                    'fa' => 'شروعی بدون هزینه با امکانات ضروری مورد نیاز هر شرکت. بدون محدودیت زمانی.',
                    'ar' => 'ابدأ مجانًا مع الميزات الأساسية التي تحتاجها كل شركة. بدون حد زمني.',
                    'ru' => 'Начните бесплатно с базовыми функциями. Без ограничения по времени.',
                    'tr' => 'Temel özelliklerle ücretsiz başlayın. Zaman sınırı yok.',
                ],
                'is_active' => true,
                'price' => 0,
                'signup_fee' => 0,
                'currency' => 'USD',
                'trial_period' => 0,
                'trial_interval' => Interval::DAY->value,
                'invoice_period' => 0, // بدون انقضا
                'invoice_interval' => Interval::MONTH->value,
                'grace_period' => 0,
                'grace_interval' => Interval::DAY->value,
                'sort_order' => 0,
            ]
        );
    }

    /**
     * @param  array<string, string>  $names
     * @param  array<string, string>  $descriptions
     * @param  array{quarterly: int, semiannual: int, yearly: int}  $prices
     */
    private function seedPaidPlan(
        string $slugPrefix,
        array $names,
        array $descriptions,
        array $prices,
        int $trialDays,
        int $sortOrderStart,
    ): void {
        $durations = [
            'quarterly' => [
                'slug_suffix' => '3-months',
                'invoice_period' => 3,
                'invoice_interval' => Interval::MONTH->value,
                'labels' => [
                    'en' => '3 Months', 'fa' => '۳ ماهه', 'ar' => '3 أشهر',
                    'ru' => '3 месяца', 'tr' => '3 Aylık',
                ],
            ],
            'semiannual' => [
                'slug_suffix' => '6-months',
                'invoice_period' => 6,
                'invoice_interval' => Interval::MONTH->value,
                'labels' => [
                    'en' => '6 Months', 'fa' => '۶ ماهه', 'ar' => '6 أشهر',
                    'ru' => '6 месяцев', 'tr' => '6 Aylık',
                ],
            ],
            'yearly' => [
                'slug_suffix' => '1-year',
                'invoice_period' => 1,
                'invoice_interval' => Interval::YEAR->value,
                'labels' => [
                    'en' => '1 Year', 'fa' => 'یکساله', 'ar' => 'سنة واحدة',
                    'ru' => '1 год', 'tr' => '1 Yıllık',
                ],
            ],
        ];

        $sortOrder = $sortOrderStart;

        foreach ($durations as $key => $duration) {
            $name = [];
            foreach ($names as $locale => $baseName) {
                $name[$locale] = $baseName.' — '.$duration['labels'][$locale];
            }

            Plan::updateOrCreate(
                ['slug' => $slugPrefix.'-'.$duration['slug_suffix']],
                [
                    'name' => $name,
                    'description' => $descriptions,
                    'is_active' => true,
                    'price' => $prices[$key],
                    'signup_fee' => 0,
                    'currency' => 'USD',
                    'trial_period' => $trialDays,
                    'trial_interval' => Interval::DAY->value,
                    'invoice_period' => $duration['invoice_period'],
                    'invoice_interval' => $duration['invoice_interval'],
                    'grace_period' => 3,
                    'grace_interval' => Interval::DAY->value,
                    'sort_order' => $sortOrder++,
                ]
            );
        }
    }
}
