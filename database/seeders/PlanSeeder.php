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

        $this->seedPaidPlan(
            slugPrefix: 'pro',
            names: [
                'en' => 'Pro', 'fa' => 'حرفه‌ای', 'ar' => 'احترافي',
                'ru' => 'Про', 'tr' => 'Pro',
            ],
            descriptions: [
                'en' => 'Advanced growth tools for exporting businesses: featured placement, RFQ, live chat, and the ViraBot assistant.',
                'fa' => 'ابزارهای پیشرفته رشد برای کسب‌وکارهای صادراتی: جایگاه ویژه، استعلام قیمت، چت آنلاین و دستیار هوشمند ویرابات.',
                'ar' => 'أدوات نمو متقدمة للأعمال المصدّرة: موضع مميز، طلب عروض الأسعار، دردشة مباشرة، والمساعد الذكي فيرابوت.',
                'ru' => 'Расширенные инструменты роста для экспортного бизнеса: приоритетное размещение, RFQ, онлайн-чат и ассистент ViraBot.',
                'tr' => 'İhracat yapan işletmeler için gelişmiş büyüme araçları: öne çıkan konum, RFQ, canlı sohbet ve ViraBot asistanı.',
            ],
            prices: ['quarterly' => 21_000_000, 'semiannual' => 33_600_000, 'yearly' => 54_600_000],
            sortOrderStart: 1,
            featureValues: $this->proFeatureValues(),
        );

        $this->seedPaidPlan(
            slugPrefix: 'pro-plus',
            names: [
                'en' => 'Pro Plus', 'fa' => 'حرفه‌ای پلاس', 'ar' => 'احترافي بلس',
                'ru' => 'Про Плюс', 'tr' => 'Pro Plus',
            ],
            descriptions: [
                'en' => 'The complete export package: premium placement, multilingual catalog, market analysis, CRM, ad banners, and API access.',
                'fa' => 'بسته کامل صادراتی: جایگاه برتر، کاتالوگ چندزبانه، تحلیل بازار، CRM، بنرهای تبلیغاتی و دسترسی API.',
                'ar' => 'الباقة التصديرية الكاملة: موضع متميز، كتالوج متعدد اللغات، تحليل الأسواق، CRM، لافتات إعلانية، ووصول API.',
                'ru' => 'Полный экспортный пакет: премиальное размещение, многоязычный каталог, анализ рынков, CRM, баннеры и доступ к API.',
                'tr' => 'Eksiksiz ihracat paketi: premium konum, çok dilli katalog, pazar analizi, CRM, reklam bannerları ve API erişimi.',
            ],
            prices: ['quarterly' => 27_000_000, 'semiannual' => 43_200_000, 'yearly' => 70_200_000],
            sortOrderStart: 4,
            featureValues: $this->proPlusFeatureValues(),
        );
    }

    /**
     * Central feature catalog: every feature is defined once with its
     * translations. Plans only reference keys with their own values.
     *
     * @return array<string, array{name: array<string, string>, resettable?: bool}>
     */
    private function featureCatalog(): array
    {
        return [
            'multilingual-profile' => ['name' => [
                'en' => 'Multilingual business profile', 'fa' => 'پروفایل چندزبانه کسب‌وکار',
                'ar' => 'ملف تعريف الأعمال متعدد اللغات', 'ru' => 'Многоязычный профиль бизнеса',
                'tr' => 'Çok dilli işletme profili',
            ]],
            'multilingual-seo' => ['name' => [
                'en' => 'Multilingual SEO', 'fa' => 'سئو چندزبانه',
                'ar' => 'تحسين محركات البحث متعدد اللغات', 'ru' => 'Многоязычное SEO',
                'tr' => 'Çok dilli SEO',
            ]],
            'contact-display' => ['name' => [
                'en' => 'Contact info & social links display', 'fa' => 'نمایش اطلاعات تماس و شبکه‌های اجتماعی',
                'ar' => 'عرض معلومات الاتصال وروابط التواصل الاجتماعي', 'ru' => 'Отображение контактов и соцсетей',
                'tr' => 'İletişim bilgileri ve sosyal medya gösterimi',
            ]],
            'analytics' => ['name' => [
                'en' => 'Visit analytics', 'fa' => 'آمار بازدید',
                'ar' => 'تحليلات الزيارات', 'ru' => 'Аналитика посещений',
                'tr' => 'Ziyaret analitiği',
            ]],
            /*'gallery-images' => ['name' => [
                'en' => 'Gallery images', 'fa' => 'تصاویر گالری',
                'ar' => 'صور المعرض', 'ru' => 'Изображения в галерее',
                'tr' => 'Galeri görselleri',
            ]],*/
            'intro-video' => ['name' => [
                'en' => 'Business intro video', 'fa' => 'ویدئوی معرفی کسب‌وکار',
                'ar' => 'فيديو تعريفي بالأعمال', 'ru' => 'Видеопрезентация бизнеса',
                'tr' => 'İşletme tanıtım videosu',
            ]],
            /*'team-members' => ['name' => [
                'en' => 'Team members', 'fa' => 'اعضای تیم',
                'ar' => 'أعضاء الفريق', 'ru' => 'Участники команды',
                'tr' => 'Ekip üyeleri',
            ]],*/
            'support' => ['name' => [
                'en' => 'Ticket support', 'fa' => 'پشتیبانی تیکتی',
                'ar' => 'دعم عبر التذاكر', 'ru' => 'Поддержка по тикетам',
                'tr' => 'Bilet ile destek',
            ]],
            'featured-placement' => ['name' => [
                'en' => 'Featured placement on high-traffic pages', 'fa' => 'جایگاه ویژه در صفحات پربازدید',
                'ar' => 'موضع مميز في الصفحات عالية الزيارة', 'ru' => 'Приоритетное размещение на популярных страницах',
                'tr' => 'Yüksek trafikli sayfalarda öne çıkan konum',
            ]],
            'premium-placement' => ['name' => [
                'en' => 'Premium placement above Pro plans', 'fa' => 'جایگاه برتر نسبت به پلن‌های حرفه‌ای',
                'ar' => 'موضع متميز فوق باقات برو', 'ru' => 'Премиальное размещение выше тарифов Про',
                'tr' => 'Pro planların üzerinde premium konum',
            ]],
            'rfq-system' => ['name' => [
                'en' => 'RFQ system with SMS & email', 'fa' => 'سیستم استعلام قیمت (RFQ) با پیامک و ایمیل',
                'ar' => 'نظام طلب عروض الأسعار مع الرسائل النصية والبريد الإلكتروني', 'ru' => 'Система запроса цен (RFQ) с SMS и email',
                'tr' => 'SMS ve e-posta ile fiyat teklifi (RFQ) sistemi',
            ]],
            /*'rfq-monthly-limit' => ['resettable' => true, 'name' => [
                'en' => 'Monthly RFQs', 'fa' => 'استعلام قیمت ماهانه',
                'ar' => 'طلبات عروض الأسعار الشهرية', 'ru' => 'Запросы цен в месяц',
                'tr' => 'Aylık RFQ sayısı',
            ]],*/
            'live-chat' => ['name' => [
                'en' => 'Online chat with AI auto-translation', 'fa' => 'چت آنلاین با ترجمه خودکار هوشمند',
                'ar' => 'دردشة مباشرة مع ترجمة تلقائية بالذكاء الاصطناعي', 'ru' => 'Онлайн-чат с ИИ-переводом',
                'tr' => 'Yapay zekâ çevirili canlı sohbet',
            ]],
            'virabot' => ['name' => [
                'en' => 'ViraBot smart assistant', 'fa' => 'دستیار هوشمند ویرابات',
                'ar' => 'المساعد الذكي فيرابوت', 'ru' => 'Умный ассистент ViraBot',
                'tr' => 'ViraBot akıllı asistan',
            ]],
            'verified-badge' => ['name' => [
                'en' => 'Verified business badge', 'fa' => 'تیک تأیید کسب‌وکار',
                'ar' => 'شارة توثيق الأعمال', 'ru' => 'Значок подтверждённого бизнеса',
                'tr' => 'Onaylı işletme rozeti',
            ]],
            'certifications' => ['name' => [
                'en' => 'Certificates & standards showcase', 'fa' => 'نمایش گواهینامه‌ها و استانداردها',
                'ar' => 'عرض الشهادات والمعايير', 'ru' => 'Демонстрация сертификатов и стандартов',
                'tr' => 'Sertifika ve standart gösterimi',
            ]],
            'virawp-monthly-contents' => ['resettable' => true, 'name' => [
                'en' => 'ViraWP monthly AI contents', 'fa' => 'تولید محتوای هوشمند ماهانه ViraWP',
                'ar' => 'محتوى ViraWP الشهري بالذكاء الاصطناعي', 'ru' => 'Ежемесячный ИИ-контент ViraWP',
                'tr' => 'Aylık ViraWP yapay zekâ içeriği',
            ]],
            'ai-content-generations' => ['resettable' => true, 'name' => [
                'en' => 'Monthly AI content generations', 'fa' => 'تولید محتوای هوشمند ماهانه',
                'ar' => 'توليد المحتوى الشهري بالذكاء الاصطناعي', 'ru' => 'Ежемесячная генерация ИИ-контента',
                'tr' => 'Aylık yapay zekâ içerik üretimi',
            ]],
            'multilingual-catalog' => ['name' => [
                'en' => 'Multilingual catalog', 'fa' => 'کاتالوگ چندزبانه',
                'ar' => 'كتالوج متعدد اللغات', 'ru' => 'Многоязычный каталог',
                'tr' => 'Çok dilli katalog',
            ]],
            'ad-banners' => ['name' => [
                'en' => 'Ad banners across Viravach platforms', 'fa' => 'بنرهای تبلیغاتی در پلتفرم‌های ویراواچ',
                'ar' => 'لافتات إعلانية عبر منصات فيراواتش', 'ru' => 'Рекламные баннеры на платформах Viravach',
                'tr' => 'Viravach platformlarında reklam bannerları',
            ]],
            'exhibitions' => ['name' => [
                'en' => 'Introduction at international exhibitions', 'fa' => 'معرفی در نمایشگاه‌های بین‌المللی',
                'ar' => 'التعريف في المعارض الدولية', 'ru' => 'Представление на международных выставках',
                'tr' => 'Uluslararası fuarlarda tanıtım',
            ]],
            'export-insights' => ['name' => [
                'en' => 'Suggested export data & insights', 'fa' => 'داده‌های صادراتی پیشنهادی',
                'ar' => 'بيانات ورؤى تصدير مقترحة', 'ru' => 'Рекомендуемые экспортные данные',
                'tr' => 'Önerilen ihracat verileri',
            ]],
            'virabot-market-analysis' => ['name' => [
                'en' => 'Target market analysis by ViraBot', 'fa' => 'بررسی بازارهای هدف توسط ویرابات',
                'ar' => 'تحليل الأسواق المستهدفة بواسطة فيرابوت', 'ru' => 'Анализ целевых рынков с ViraBot',
                'tr' => 'ViraBot ile hedef pazar analizi',
            ]],
            'crm' => ['name' => [
                'en' => 'Advanced CRM', 'fa' => 'CRM پیشرفته',
                'ar' => 'إدارة علاقات العملاء المتقدمة', 'ru' => 'Продвинутая CRM',
                'tr' => 'Gelişmiş CRM',
            ]],
            'no-competitor-ads' => ['name' => [
                'en' => 'No competitor ads on your profile', 'fa' => 'حذف تبلیغات رقبا از پروفایل شما',
                'ar' => 'بدون إعلانات المنافسين في ملفك', 'ru' => 'Без рекламы конкурентов в вашем профиле',
                'tr' => 'Profilinizde rakip reklamı yok',
            ]],
            'api-access' => ['name' => [
                'en' => 'API access', 'fa' => 'دسترسی API',
                'ar' => 'الوصول إلى واجهة برمجة التطبيقات', 'ru' => 'Доступ к API',
                'tr' => 'API erişimi',
            ]],
        ];
    }

    /** @return array<string, string> */
    private function freeFeatureValues(): array
    {
        return [
            'multilingual-profile' => 'true',
            'multilingual-seo' => 'true',
            'contact-display' => 'true',
            'analytics' => 'basic',
            // 'gallery-images' => '5',
            // 'team-members' => '1',
            'support' => 'true',
            'ai-content-generations' => '1',
            'virawp-monthly-contents' => '1',
        ];
    }

    /** @return array<string, string> */
    private function proFeatureValues(): array
    {
        return [
            ...$this->freeFeatureValues(),
            'analytics' => 'standard',
            // 'gallery-images' => '20',
            // 'team-members' => '3',
            'intro-video' => 'true',
            'featured-placement' => 'true',
            'rfq-system' => 'true',
            // 'rfq-monthly-limit' => '30',
            'live-chat' => 'true',
            'virabot' => 'true',
            'verified-badge' => 'true',
            'certifications' => 'true',
            'virawp-monthly-contents' => '3',
            'ai-content-generations' => '3',
        ];
    }

    /** @return array<string, string> */
    private function proPlusFeatureValues(): array
    {
        return [
            ...$this->proFeatureValues(),
            'analytics' => 'advanced',
            // 'gallery-images' => '50',
            // 'team-members' => '10',
            // 'rfq-monthly-limit' => 'unlimited',
            'virawp-monthly-contents' => '10',
            'ai-content-generations' => '10',
            'premium-placement' => 'true',
            'multilingual-catalog' => 'true',
            'ad-banners' => 'true',
            'exhibitions' => 'true',
            'export-insights' => 'true',
            'virabot-market-analysis' => 'true',
            'crm' => 'true',
            'no-competitor-ads' => 'true',
            'api-access' => 'true',
        ];
    }

    private function seedFreePlan(): void
    {
        $plan = Plan::updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => [
                    'en' => 'Basic', 'fa' => 'پایه', 'ar' => 'أساسي',
                    'ru' => 'Базовый', 'tr' => 'Temel',
                ],
                'description' => [
                    'en' => 'Automatically activated for every business: a multilingual public profile with SEO, contact display, and visit reports. No time limit.',
                    'fa' => 'به‌صورت خودکار برای هر کسب‌وکار فعال می‌شود: پروفایل عمومی چندزبانه با سئو، نمایش اطلاعات تماس و گزارش بازدید. بدون محدودیت زمانی.',
                    'ar' => 'يُفعَّل تلقائيًا لكل الأعمال: ملف تعريف عام متعدد اللغات مع SEO وعرض معلومات الاتصال وتقارير الزيارات. بدون حد زمني.',
                    'ru' => 'Активируется автоматически для каждого бизнеса: многоязычный публичный профиль с SEO, контактами и отчётами о посещениях. Без ограничения по времени.',
                    'tr' => 'Her işletme için otomatik etkinleştirilir: SEO destekli çok dilli genel profil, iletişim gösterimi ve ziyaret raporları. Zaman sınırı yok.',
                ],
                'is_active' => true,
                'price' => 0,
                'signup_fee' => 0,
                'currency' => 'IRT',
                'trial_period' => 0,
                'trial_interval' => Interval::DAY->value,
                'invoice_period' => 0, // Never expires
                'invoice_interval' => Interval::MONTH->value,
                'grace_period' => 0,
                'grace_interval' => Interval::DAY->value,
                'sort_order' => 0,
            ]
        );

        $this->seedFeatures($plan, $this->freeFeatureValues());
    }

    /**
     * @param  array<string, string>  $names
     * @param  array<string, string>  $descriptions
     * @param  array{quarterly: int, semiannual: int, yearly: int}  $prices
     * @param  array<string, string>  $featureValues
     */
    private function seedPaidPlan(
        string $slugPrefix,
        array $names,
        array $descriptions,
        array $prices,
        int $sortOrderStart,
        array $featureValues,
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

            $plan = Plan::updateOrCreate(
                ['slug' => $slugPrefix.'-'.$duration['slug_suffix']],
                [
                    'name' => $name,
                    'description' => $descriptions,
                    'is_active' => true,
                    'price' => $prices[$key],
                    'signup_fee' => 0,
                    'currency' => 'IRT',
                    // 14-day Pro Plus trial for the user's first business is a
                    // user-level business rule, enforced in the application
                    // layer — not via plan-level trial periods.
                    'trial_period' => 0,
                    'trial_interval' => Interval::DAY->value,
                    'invoice_period' => $duration['invoice_period'],
                    'invoice_interval' => $duration['invoice_interval'],
                    'grace_period' => 3,
                    'grace_interval' => Interval::DAY->value,
                    'sort_order' => $sortOrder++,
                ]
            );

            $this->seedFeatures($plan, $featureValues);
        }
    }

    /**
     * @param  array<string, string>  $featureValues
     */
    private function seedFeatures(Plan $plan, array $featureValues): void
    {
        $catalog = $this->featureCatalog();
        $sortOrder = 0;

        foreach ($featureValues as $key => $value) {
            $definition = $catalog[$key];
            $resettable = $definition['resettable'] ?? false;

            // The package's features table has a globally unique slug index,
            // so each feature slug must be prefixed with its plan slug.
            $plan->features()->updateOrCreate(
                ['slug' => $plan->slug.'-'.$key],
                [
                    'name' => $definition['name'],
                    'value' => $value,
                    'resettable_period' => $resettable ? 1 : 0,
                    'resettable_interval' => Interval::MONTH->value,
                    'sort_order' => $sortOrder++,
                ]
            );
        }
    }
}
