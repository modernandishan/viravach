<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class HomePageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => '/'],
            [
                'title' => [
                    'en' => 'Viravach',
                    'fa' => 'ویراواچ',
                    'ar' => 'ويراواتش',
                    'ru' => 'Виравач',
                    'tr' => 'Viravatç',
                ],
                'h1' => [
                    'fa' => 'دایرکتوری صادرکنندگان و تأمین‌کنندگان ایرانی',
                    'en' => 'The directory of Iranian exporters and suppliers',
                    'ar' => 'دليل المصدّرين والموردين الإيرانيين',
                    'ru' => 'Каталог иранских экспортёров и поставщиков',
                    'tr' => 'İranlı ihracatçılar ve tedarikçiler dizini',
                ],
                'subheading' => [
                    'fa' => 'شرکت‌های ایرانی را بر اساس دسته‌بندی، استان و بازار هدف پیدا کنید.',
                    'en' => 'Find Iranian companies by category, province and target market.',
                    'ar' => 'ابحث عن الشركات الإيرانية حسب الفئة والمحافظة وسوق الهدف.',
                    'ru' => 'Находите иранские компании по категории, провинции и целевому рынку.',
                    'tr' => 'İran şirketlerini kategori, eyalet ve hedef pazara göre bulun.',
                ],
                'intro_body' => [
                    'fa' => '',
                    'en' => '',
                    'ar' => '',
                    'ru' => '',
                    'tr' => '',
                ],
                'is_active' => true,
                'published_at' => now(),
                'sort_order' => 0,
            ]
        );

        // Seed-default SEO for the homepage; the admin overrides it in the
        // Filament Page resource (SeoMeta section).
        $page->seo()->updateOrCreate([], [
            'meta_title' => [
                'fa' => 'ویراواچ | دایرکتوری صادرکنندگان ایرانی برای خریداران بین‌المللی',
                'en' => 'Viravach | Directory of Iranian exporters for international buyers',
                'ar' => 'ویراواتش | دليل المصدّرين الإيرانيين للمشترين الدوليين',
                'ru' => 'Виравач | Каталог иранских экспортёров для международных покупателей',
                'tr' => 'Viravatç | Uluslararası alıcılar için İranlı ihracatçılar dizini',
            ],
            'meta_description' => [
                'fa' => 'ویراواچ شرکت‌ها و تأمین‌کنندگان ایرانی را در دسته‌بندی‌ها و استان‌های مختلف به خریداران بین‌المللی معرفی می‌کند.',
                'en' => 'Viravach introduces Iranian companies and suppliers across categories and provinces to international buyers.',
                'ar' => 'يقدّم ویراواتش الشركات والموردين الإيرانيين في فئات ومحافظات مختلفة إلى المشترين الدوليين.',
                'ru' => 'Виравач представляет иранские компании и поставщиков в разных категориях и провинциях международным покупателям.',
                'tr' => 'Viravatç, İran şirketlerini ve tedarikçilerini farklı kategoriler ve eyaletlerde uluslararası alıcılara tanıtır.',
            ],
            'robots_index' => true,
            'robots_follow' => true,
            'sitemap_include' => true,
        ]);
    }
}
