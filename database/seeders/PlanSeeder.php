<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Laravelcm\Subscriptions\Interval;

class PlanSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
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
                    'en' => 'Get started at no cost with the essential features every company needs.',
                    'fa' => 'شروعی بدون هزینه با امکانات ضروری مورد نیاز هر شرکت.',
                    'ar' => 'ابدأ مجانًا مع الميزات الأساسية التي تحتاجها كل شركة.',
                    'ru' => 'Начните бесплатно с базовыми функциями, необходимыми каждой компании.',
                    'tr' => 'Her şirketin ihtiyaç duyduğu temel özelliklerle ücretsiz başlayın.',
                ],
                'is_active' => true,
                'price' => 0,
                'signup_fee' => 0,
                'currency' => 'USD',
                'trial_period' => 0,
                'trial_interval' => Interval::DAY->value,
                'invoice_period' => 0,
                'invoice_interval' => Interval::MONTH->value,
                'grace_period' => 0,
                'grace_interval' => Interval::DAY->value,
                'prorate_day' => null,
                'prorate_period' => null,
                'prorate_extend_due' => null,
                'active_subscribers_limit' => null,
                'sort_order' => 0,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => [
                    'en' => 'Pro',
                    'fa' => 'حرفه‌ای',
                    'ar' => 'احترافي',
                    'ru' => 'Про',
                    'tr' => 'Pro',
                ],
                'description' => [
                    'en' => 'Advanced tools for growing companies, with a 30-day free trial.',
                    'fa' => 'ابزارهای پیشرفته برای شرکت‌های در حال رشد، به‌همراه ۳۰ روز دوره آزمایشی رایگان.',
                    'ar' => 'أدوات متقدمة للشركات النامية، مع فترة تجريبية مجانية لمدة 30 يومًا.',
                    'ru' => 'Расширенные инструменты для растущих компаний с 30-дневным бесплатным пробным периодом.',
                    'tr' => 'Büyüyen şirketler için gelişmiş araçlar, 30 günlük ücretsiz deneme süresiyle.',
                ],
                'is_active' => true,
                'price' => 29,
                'signup_fee' => 0,
                'currency' => 'USD',
                'trial_period' => 30,
                'trial_interval' => Interval::DAY->value,
                'invoice_period' => 1,
                'invoice_interval' => Interval::MONTH->value,
                'grace_period' => 3,
                'grace_interval' => Interval::DAY->value,
                'prorate_day' => null,
                'prorate_period' => null,
                'prorate_extend_due' => null,
                'active_subscribers_limit' => null,
                'sort_order' => 1,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'pro-plus'],
            [
                'name' => [
                    'en' => 'Pro Plus',
                    'fa' => 'حرفه‌ای پلاس',
                    'ar' => 'احترافي بلس',
                    'ru' => 'Про Плюс',
                    'tr' => 'Pro Plus',
                ],
                'description' => [
                    'en' => 'The complete package for larger companies that need our highest tier of features and support.',
                    'fa' => 'بسته کامل برای شرکت‌های بزرگ‌تر که به بالاترین سطح امکانات و پشتیبانی نیاز دارند.',
                    'ar' => 'الباقة الكاملة للشركات الكبرى التي تحتاج إلى أعلى مستوى من الميزات والدعم لدينا.',
                    'ru' => 'Полный пакет для крупных компаний, которым нужен наш максимальный уровень функций и поддержки.',
                    'tr' => 'En üst düzey özelliklerimize ve desteğimize ihtiyaç duyan büyük şirketler için eksiksiz paket.',
                ],
                'is_active' => true,
                'price' => 79,
                'signup_fee' => 0,
                'currency' => 'USD',
                'trial_period' => 0,
                'trial_interval' => Interval::DAY->value,
                'invoice_period' => 1,
                'invoice_interval' => Interval::MONTH->value,
                'grace_period' => 3,
                'grace_interval' => Interval::DAY->value,
                'prorate_day' => null,
                'prorate_period' => null,
                'prorate_extend_due' => null,
                'active_subscribers_limit' => null,
                'sort_order' => 2,
            ]
        );
    }
}
