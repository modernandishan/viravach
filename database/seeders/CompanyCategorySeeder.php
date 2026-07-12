<?php

namespace Database\Seeders;

use App\Models\CompanyCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanyCategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $agriculture_food_processing_industries = CompanyCategory::factory()->create([
            'parent_id' => null,
            'slug' => 'agriculture-food-processing-industries',
            'title' => [
                'en' => 'Agriculture, Food & Processing Industries',
                'fa' => 'کشاورزی، مواد غذایی و صنایع تبدیلی',
                'tr' => 'Tarım, Gıda ve İşleme Endüstrileri',
                'ru' => 'Сельское хозяйство, пищевая и перерабатывающая промышленность',
                'ar' => 'الزراعة، الأغذية والصناعات التحويلية',
            ],
            'sort_order' => 0,
        ]);

        $industry_machinery_equipment = CompanyCategory::factory()->create([
            'parent_id' => null,
            'slug' => 'industry-machinery-equipment',
            'title' => [
                'en' => 'Industry, Machinery & Equipment',
                'fa' => 'صنعت، ماشین‌آلات و تجهیزات',
                'tr' => 'Sanayi, Makine ve Ekipman',
                'ru' => 'Промышленность, машины и оборудование',
                'ar' => 'الصناعة، الآلات والمعدات',
            ],
            'sort_order' => 0,
        ]);

        $oil_gas_petrochemicals_polymers = CompanyCategory::factory()->create([
            'parent_id' => null,
            'slug' => 'oil-gas-petrochemicals-polymers',
            'title' => [
                'en' => 'Oil, Gas, Petrochemicals & Polymers',
                'fa' => 'نفت، گاز، پتروشیمی و پلیمر',
                'tr' => 'Petrol, Gaz, Petrokimya ve Polimerler',
                'ru' => 'Нефть, газ, нефтехимия и полимеры',
                'ar' => 'النفط، الغاز، البتروكيماويات والبوليمرات',
            ],
            'sort_order' => 0,
        ]);

        $mining_metals_stones_building_materials = CompanyCategory::factory()->create([
            'parent_id' => null,
            'slug' => 'mining-metals-stones-building-materials',
            'title' => [
                'en' => 'Mining, Metals, Stones & Building Materials',
                'fa' => 'معدن، فلزات، سنگ و مصالح ساختمانی',
                'tr' => 'Madencilik, Metaller, Taşlar ve Yapı Malzemeleri',
                'ru' => 'Горнодобывающая промышленность, металлы, камни и строительные материалы',
                'ar' => 'التعدين، المعادن، الأحجار ومواد البناء',
            ],
            'sort_order' => 0,
        ]);

        $automotive_parts_spare_parts = CompanyCategory::factory()->create([
            'parent_id' => null,
            'slug' => 'automotive-parts-spare-parts',
            'title' => [
                'en' => 'Automotive, Parts & Spare Parts',
                'fa' => 'خودرو، قطعات و لوازم یدکی',
                'tr' => 'Otomotiv, Parçalar ve Yedek Parçalar',
                'ru' => 'Автомобилестроение, запчасти и комплектующие',
                'ar' => 'السيارات، قطع الغيار والإكسسوارات',
            ],
            'sort_order' => 0,
        ]);

        $textiles_apparel_leather_cosmetics = CompanyCategory::factory()->create([
            'parent_id' => null,
            'slug' => 'textiles-apparel-leather-cosmetics',
            'title' => [
                'en' => 'Textiles, Apparel, Leather & Cosmetics',
                'fa' => 'نساجی، پوشاک، چرم و آرایشی‌بهداشتی',
                'tr' => 'Tekstil, Giyim, Deri ve Kozmetik',
                'ru' => 'Текстиль, одежда, кожа и косметика',
                'ar' => 'المنسوجات، الملابس، الجلود ومستحضرات التجميل',
            ],
            'sort_order' => 0,
        ]);

        $carpets_handicrafts_arts = CompanyCategory::factory()->create([
            'parent_id' => null,
            'slug' => 'carpets-handicrafts-arts',
            'title' => [
                'en' => 'Carpets, Handicrafts & Arts',
                'fa' => 'فرش، صنایع دستی و هنر',
                'tr' => 'Halılar, El Sanatları ve Sanat',
                'ru' => 'Ковры, изделия народных промыслов и искусство',
                'ar' => 'السجاد، الحرف اليدوية والفنون',
            ],
            'sort_order' => 0,
        ]);

        $pharmaceuticals_medical_healthcare_equipment = CompanyCategory::factory()->create([
            'parent_id' => null,
            'slug' => 'pharmaceuticals-medical-healthcare-equipment',
            'title' => [
                'en' => 'Pharmaceuticals, Medical & Healthcare Equipment',
                'fa' => 'داروسازی، تجهیزات پزشکی و بهداشت',
                'tr' => 'İlaç, Tıbbi ve Sağlık Ekipmanları',
                'ru' => 'Фармацевтика, медицинское и оздоровительное оборудование',
                'ar' => 'المستحضرات الصيدلانية، المعدات الطبية والرعاية الصحية',
            ],
            'sort_order' => 0,
        ]);

        $it_communications_knowledge_based = CompanyCategory::factory()->create([
            'parent_id' => null,
            'slug' => 'it-communications-knowledge-based',
            'title' => [
                'en' => 'IT, Communications & Knowledge-Based',
                'fa' => 'فناوری اطلاعات، ارتباطات و دانش‌بنیان',
                'tr' => 'BT, İletişim ve Bilgi Tabanlı Teknolojiler',
                'ru' => 'ИТ, связь и наукоёмкие технологии',
                'ar' => 'تكنولوجيا المعلومات، الاتصالات والتقنيات القائمة على المعرفة',
            ],
            'sort_order' => 0,
        ]);

        $trade_services_logistics_transportation = CompanyCategory::factory()->create([
            'parent_id' => null,
            'slug' => 'trade-services-logistics-transportation',
            'title' => [
                'en' => 'Trade Services, Logistics & Transportation',
                'fa' => 'خدمات بازرگانی، لجستیک و حمل‌ونقل',
                'tr' => 'Ticari Hizmetler, Lojistik ve Ulaştırma',
                'ru' => 'Торговые услуги, логистика и транспорт',
                'ar' => 'الخدمات التجارية، اللوجستيات والنقل',
            ],
            'sort_order' => 0,
        ]);

        $business_opportunities_partnerships_investments = CompanyCategory::factory()->create([
            'parent_id' => null,
            'slug' => 'business-opportunities-partnerships-investments',
            'title' => [
                'en' => 'Business Opportunities, Partnerships & Investments',
                'fa' => 'فرصت‌های تجاری، مشارکت و سرمایه‌گذاری',
                'tr' => 'İş Fırsatları, Ortaklıklar ve Yatırımlar',
                'ru' => 'Деловые возможности, партнерство и инвестиции',
                'ar' => 'فرص الأعمال، الشراكات والاستثمارات',
            ],
            'sort_order' => 0,
        ]);

        $business_education_consulting_international_events = CompanyCategory::factory()->create([
            'parent_id' => null,
            'slug' => 'business-education-consulting-international-events',
            'title' => [
                'en' => 'Business Education, Consulting & International Events',
                'fa' => 'آموزش تجاری، مشاوره و رویدادهای بین‌المللی',
                'tr' => 'İş Eğitimi, Danışmanlık ve Uluslararası Etkinlikler',
                'ru' => 'Деловое образование, консалтинг и международные мероприятия',
                'ar' => 'التعليم التجاري، الاستشارات والفعاليات الدولية',
            ],
            'sort_order' => 0,
        ]);

        /*foreach (range(1, 2) as $subIndex) {
            $subCategory = CompanyCategory::factory()->create([
                'parent_id' => $parent->id,
                'slug' => "test-sub-category-{$subIndex}",
                'title' => [
                    'en' => "Test Sub Category {$subIndex}",
                    'fa' => "زیر دسته تست {$subIndex}",
                ],
                'sort_order' => $subIndex,
            ]);

            foreach (range(1, 3) as $childIndex) {
                CompanyCategory::factory()->create([
                    'parent_id' => $subCategory->id,
                    'slug' => "test-sub-category-{$subIndex}-child-{$childIndex}",
                    'title' => [
                        'en' => "Test Sub Category {$subIndex} Child {$childIndex}",
                        'fa' => "زیر دسته {$subIndex} فرزند {$childIndex}",
                    ],
                    'sort_order' => $childIndex,
                ]);
            }
        }*/

    }
}
