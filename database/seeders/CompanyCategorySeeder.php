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

        $subCategories = [
            [
                'slug' => 'saffron-and-medicinal-plants',
                'title' => [
                    'en' => 'Saffron and Medicinal Plants',
                    'fa' => 'زعفران و گیاهان دارویی',
                    'tr' => 'Safran ve Tıbbi Bitkiler',
                    'ru' => 'Шафран и лекарственные растения',
                    'ar' => 'الزعفران والنباتات الطبية',
                ],
            ],
            [
                'slug' => 'dried-fruits-and-nuts',
                'title' => [
                    'en' => 'Dried Fruits and Nuts (Pistachio, Almond, Raisin, Fig)',
                    'fa' => 'خشکبار و آجیل (پسته، بادام، کشمش، انجیر)',
                    'tr' => 'Kuruyemiş ve Çerezler (Antep fıstığı, Badem, Kuru üzüm, İncir)',
                    'ru' => 'Сухофрукты и орехи (Фисташки, Миндаль, Изюм, Инжир)',
                    'ar' => 'الفواكه المجففة والمكسرات (فستق، لوز، زبيب، تين)',
                ],
            ],
            [
                'slug' => 'dates',
                'title' => [
                    'en' => 'Dates',
                    'fa' => 'خرما',
                    'tr' => 'Hurma',
                    'ru' => 'Финики',
                    'ar' => 'التمور',
                ],
            ],
            [
                'slug' => 'fresh-fruits-and-vegetables',
                'title' => [
                    'en' => 'Fresh Fruits and Vegetables',
                    'fa' => 'میوه و تره‌بار تازه',
                    'tr' => 'Taze Meyve ve Sebze',
                    'ru' => 'Свежие фрукты и овощи',
                    'ar' => 'الفواكه والخضروات الطازجة',
                ],
            ],
            [
                'slug' => 'dairy-products',
                'title' => [
                    'en' => 'Dairy Products',
                    'fa' => 'محصولات لبنی',
                    'tr' => 'Süt Ürünleri',
                    'ru' => 'Молочные продукты',
                    'ar' => 'منتجات الألبان',
                ],
            ],
            [
                'slug' => 'sweets-chocolate-and-confectionery',
                'title' => [
                    'en' => 'Sweets, Chocolate and Confectionery',
                    'fa' => 'شیرینی، شکلات و قنادی',
                    'tr' => 'Şekerleme, Çikolata ve Pastacılık',
                    'ru' => 'Сладости, шоколад и кондитерские изделия',
                    'ar' => 'الحلويات، الشوكولاتة والمعجنات',
                ],
            ],
            [
                'slug' => 'canned-and-ready-meals',
                'title' => [
                    'en' => 'Canned and Ready Meals (Paste, Jam, Pickles)',
                    'fa' => 'کنسرو و غذاهای آماده (رب، مربا، ترشیجات)',
                    'tr' => 'Konserve ve Hazır Yemekler (Salça, Reçel, Turşu)',
                    'ru' => 'Консервы и готовые блюда (Паста, Джем, Соленья)',
                    'ar' => 'الأطعمة المعلبة والجاهزة (معجون، مربى، مخللات)',
                ],
            ],
            [
                'slug' => 'edible-oils',
                'title' => [
                    'en' => 'Edible Oils',
                    'fa' => 'روغن‌های خوراکی',
                    'tr' => 'Yenilebilir Yağlar',
                    'ru' => 'Пищевые масла',
                    'ar' => 'الزيوت الصالحة للأكل',
                ],
            ],
            [
                'slug' => 'beverages',
                'title' => [
                    'en' => 'Beverages (Juice, Malt, Mineral Water)',
                    'fa' => 'نوشیدنی‌ها (آبمیوه، ماءالشعیر، آب معدنی)',
                    'tr' => 'İçecekler (Meyve suyu, Malt, Maden suyu)',
                    'ru' => 'Напитки (Сок, Солод, Минеральная вода)',
                    'ar' => 'المشروبات (عصير، شعير، مياه معدنية)',
                ],
            ],
            [
                'slug' => 'cereals-legumes-and-flour',
                'title' => [
                    'en' => 'Cereals, Legumes and Flour',
                    'fa' => 'غلات، حبوبات و آرد',
                    'tr' => 'Tahıllar, Baklagiller ve Un',
                    'ru' => 'Зерновые, бобовые и мука',
                    'ar' => 'الحبوب، البقوليات والدقيق',
                ],
            ],
            [
                'slug' => 'tea-and-coffee',
                'title' => [
                    'en' => 'Tea and Coffee',
                    'fa' => 'چای و قهوه',
                    'tr' => 'Çay ve Kahve',
                    'ru' => 'Чай и кофе',
                    'ar' => 'الشاي والقهوة',
                ],
            ],
            [
                'slug' => 'spices-and-condiments',
                'title' => [
                    'en' => 'Spices and Condiments',
                    'fa' => 'ادویه‌جات و چاشنی‌ها',
                    'tr' => 'Baharat ve Çeşniler',
                    'ru' => 'Специи и приправы',
                    'ar' => 'التوابل والبهارات',
                ],
            ],
            [
                'slug' => 'honey-and-bee-products',
                'title' => [
                    'en' => 'Honey and Bee Products',
                    'fa' => 'عسل و فرآورده‌های زنبور عسل',
                    'tr' => 'Bal ve Arı Ürünleri',
                    'ru' => 'Мед и продукты пчеловодства',
                    'ar' => 'العسل ومنتجات النحل',
                ],
            ],
            [
                'slug' => 'fisheries-and-aquatic-products',
                'title' => [
                    'en' => 'Fisheries and Aquatic Products',
                    'fa' => 'شیلات و آبزیان',
                    'tr' => 'Su Ürünleri ve Denizcilik',
                    'ru' => 'Рыболовство и водные биоресурсы',
                    'ar' => 'مصايد الأسماك والكائنات المائية',
                ],
            ],
            [
                'slug' => 'meat-poultry-and-protein-products',
                'title' => [
                    'en' => 'Meat, Poultry and Protein Products',
                    'fa' => 'گوشت، مرغ و فرآورده‌های پروتئینی',
                    'tr' => 'Et, Kümes Hayvanları ve Protein Ürünleri',
                    'ru' => 'Мясо, птица и белковые продукты',
                    'ar' => 'اللحوم، الدواجن والمنتجات البروتينية',
                ],
            ],
            [
                'slug' => 'agricultural-inputs',
                'title' => [
                    'en' => 'Agricultural Inputs (Seeds, Fertilizers, Pesticides)',
                    'fa' => 'نهاده‌های کشاورزی (بذر، کود، سم)',
                    'tr' => 'Tarımsal Girdiler (Tohum, Gübre, İlaç)',
                    'ru' => 'Сельскохозяйственные ресурсы (Семена, Удобрения, Пестициды)',
                    'ar' => 'المدخلات الزراعية (بذور، أسمدة، مبيدات)',
                ],
            ],
            [
                'slug' => 'flowers-and-ornamental-plants',
                'title' => [
                    'en' => 'Flowers and Ornamental Plants',
                    'fa' => 'گل و گیاهان زینتی',
                    'tr' => 'Çiçek ve Süs Bitkileri',
                    'ru' => 'Цветы и декоративные растения',
                    'ar' => 'الزهور والنباتات الزينة',
                ],
            ],
        ];

        foreach ($subCategories as $index => $subCategoryData) {
            CompanyCategory::factory()->create([
                'parent_id' => $agriculture_food_processing_industries->id,
                'slug' => $subCategoryData['slug'],
                'title' => $subCategoryData['title'],
                'sort_order' => $index + 1,
            ]);
        }

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

        $industrySubCategories = [
            [
                'slug' => 'industrial-machinery-production-lines',
                'title' => [
                    'en' => 'Industrial Machinery and Production Lines',
                    'fa' => 'ماشین‌آلات صنعتی و خطوط تولید',
                    'tr' => 'Endüstriyel Makineler ve Üretim Hatları',
                    'ru' => 'Промышленное оборудование и производственные линии',
                    'ar' => 'الآلات الصناعية وخطوط الإنتاج',
                ],
            ],
            [
                'slug' => 'food-industry-packaging-equipment',
                'title' => [
                    'en' => 'Food Industry and Packaging Equipment',
                    'fa' => 'تجهیزات صنایع غذایی و بسته‌بندی',
                    'tr' => 'Gıda Sanayi ve Ambalajlama Ekipmanları',
                    'ru' => 'Оборудование для пищевой и упаковочной промышленности',
                    'ar' => 'معدات صناعات الأغذية والتغليف',
                ],
            ],
            [
                'slug' => 'construction-road-building-machinery',
                'title' => [
                    'en' => 'Construction and Road Building Machinery',
                    'fa' => 'ماشین‌آلات ساختمانی و راهسازی',
                    'tr' => 'İnşaat ve Yol Yapım Makineleri',
                    'ru' => 'Строительная и дорожная техника',
                    'ar' => 'آلات البناء وبناء الطرق',
                ],
            ],
            [
                'slug' => 'mining-mineral-processing-equipment',
                'title' => [
                    'en' => 'Mining and Mineral Processing Equipment',
                    'fa' => 'تجهیزات معدنی و فرآوری مواد معدنی',
                    'tr' => 'Madencilik ve Mineral İşleme Ekipmanları',
                    'ru' => 'Горнодобывающее и оборудование для переработки минералов',
                    'ar' => 'معدات التعدين ومعالجة المعادن',
                ],
            ],
            [
                'slug' => 'oil-gas-petrochemical-equipment',
                'title' => [
                    'en' => 'Oil, Gas and Petrochemical Equipment',
                    'fa' => 'تجهیزات نفت، گاز و پتروشیمی',
                    'tr' => 'Petrol, Gaz ve Petrokimya Ekipmanları',
                    'ru' => 'Оборудование для нефтегазовой и нефтехимической промышленности',
                    'ar' => 'معدات النفط والغاز والبتروكيماويات',
                ],
            ],
            [
                'slug' => 'electrical-energy-equipment',
                'title' => [
                    'en' => 'Electrical and Energy Equipment (Transformers, Generators, Switchgears)',
                    'fa' => 'تجهیزات برق و انرژی (ترانس، ژنراتور، تابلو برق)',
                    'tr' => 'Elektrik ve Enerji Ekipmanları (Transformatörler, Jeneratörler, Panolar)',
                    'ru' => 'Электрооборудование и энергетическое оборудование (Трансформаторы, Генераторы, Щиты)',
                    'ar' => 'معدات الكهرباء والطاقة (محولات، مولدات، لوحات تحكم)',
                ],
            ],
            [
                'slug' => 'pumps-compressors-fluid-equipment',
                'title' => [
                    'en' => 'Pumps, Compressors and Fluid Equipment',
                    'fa' => 'پمپ، کمپرسور و تجهیزات سیالات',
                    'tr' => 'Pompalar, Kompresörler ve Akışkan Ekipmanları',
                    'ru' => 'Насосы, компрессоры и оборудование для работы с жидкостями',
                    'ar' => 'المضخات، الضواغط ومعدات السوائل',
                ],
            ],
            [
                'slug' => 'industrial-hvac-equipment',
                'title' => [
                    'en' => 'Industrial Ventilation, Cooling and Heating Equipment',
                    'fa' => 'تجهیزات تهویه، سرمایش و گرمایش صنعتی',
                    'tr' => 'Endüstriyel Havalandırma, Soğutma ve Isıtma Ekipmanları',
                    'ru' => 'Промышленное вентиляционное, охлаждающее и отопительное оборудование',
                    'ar' => 'معدات التهوية الصناعية والتبريد والتدفئة',
                ],
            ],
            [
                'slug' => 'industrial-tools-equipment',
                'title' => [
                    'en' => 'Industrial Tools and Equipment',
                    'fa' => 'ابزار و ابزارآلات صنعتی',
                    'tr' => 'Endüstriyel Aletler ve Ekipmanlar',
                    'ru' => 'Промышленные инструменты и оборудование',
                    'ar' => 'الأدوات والمعدات الصناعية',
                ],
            ],
            [
                'slug' => 'industrial-automation-instrumentation',
                'title' => [
                    'en' => 'Industrial Automation and Instrumentation',
                    'fa' => 'اتوماسیون صنعتی و ابزار دقیق',
                    'tr' => 'Endüstriyel Otomasyon ve Enstrümantasyon',
                    'ru' => 'Промышленная автоматизация и контрольно-измерительные приборы',
                    'ar' => 'الأتمتة الصناعية والأدوات الدقيقة',
                ],
            ],
            [
                'slug' => 'industrial-parts-components',
                'title' => [
                    'en' => 'Industrial Parts (Bearings, Industrial Valves, Fittings)',
                    'fa' => 'قطعات صنعتی (بلبرینگ، شیرآلات صنعتی، اتصالات)',
                    'tr' => 'Endüstriyel Parçalar (Rulmanlar, Endüstriyel Vanalar, Bağlantı Parçaları)',
                    'ru' => 'Промышленные детали (Подшипники, Промышленные клапаны, Фитинги)',
                    'ar' => 'القطع الصناعية (محامل، صمامات صناعية، توصيلات)',
                ],
            ],
            [
                'slug' => 'welding-cutting-equipment',
                'title' => [
                    'en' => 'Welding and Cutting Equipment',
                    'fa' => 'تجهیزات جوش و برش',
                    'tr' => 'Kaynak ve Kesme Ekipmanları',
                    'ru' => 'Сварочное и резательное оборудование',
                    'ar' => 'معدات اللحام والقطع',
                ],
            ],
            [
                'slug' => 'elevators-hoists-cranes',
                'title' => [
                    'en' => 'Elevators, Hoists and Cranes',
                    'fa' => 'آسانسور، بالابر و جرثقیل',
                    'tr' => 'Asansörler, Kaldırıcılar ve Vinçler',
                    'ru' => 'Лифты, подъемники и краны',
                    'ar' => 'المصاعد، الرافعات والروافع',
                ],
            ],
            [
                'slug' => 'laboratory-measuring-equipment',
                'title' => [
                    'en' => 'Laboratory and Measuring Equipment',
                    'fa' => 'تجهیزات آزمایشگاهی و اندازه‌گیری',
                    'tr' => 'Laboratuvar ve Ölçüm Ekipmanları',
                    'ru' => 'Лабораторное и измерительное оборудование',
                    'ar' => 'معدات المختبرات والقياس',
                ],
            ],
            [
                'slug' => 'mold-making-casting',
                'title' => [
                    'en' => 'Mold Making and Casting',
                    'fa' => 'قالب‌سازی و ریخته‌گری',
                    'tr' => 'Kalıpçılık ve Döküm',
                    'ru' => 'Изготовление пресс-форм и литье',
                    'ar' => 'صناعة القوالب والمسبك',
                ],
            ],
            [
                'slug' => 'agricultural-livestock-equipment',
                'title' => [
                    'en' => 'Agricultural and Livestock Equipment',
                    'fa' => 'تجهیزات کشاورزی و دامپروری',
                    'tr' => 'Tarımsal ve Hayvancılık Ekipmanları',
                    'ru' => 'Сельскохозяйственное и животноводческое оборудование',
                    'ar' => 'معدات الزراعة وتربية الماشية',
                ],
            ],
        ];

        foreach ($industrySubCategories as $index => $subCategoryData) {
            CompanyCategory::factory()->create([
                'parent_id' => $industry_machinery_equipment->id,
                'slug' => $subCategoryData['slug'],
                'title' => $subCategoryData['title'],
                'sort_order' => $index + 1,
            ]);
        }

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

        $oilGasSubCategories = [
            [
                'slug' => 'crude-oil-gas-condensates',
                'title' => [
                    'en' => 'Crude Oil and Gas Condensates',
                    'fa' => 'نفت خام و میعانات گازی',
                    'tr' => 'Ham Petrol ve Gaz Kondansatları',
                    'ru' => 'Сырая нефть и газовый конденсат',
                    'ar' => 'النفط الخام والمكثفات الغازية',
                ],
            ],
            [
                'slug' => 'petroleum-products',
                'title' => [
                    'en' => 'Petroleum Products (Gasoline, Diesel, Kerosene, Fuel)',
                    'fa' => 'فرآورده‌های نفتی (بنزین، گازوئیل، نفت سفید، سوخت)',
                    'tr' => 'Petrol Ürünleri (Benzin, Motorin, Gazyağı, Yakıt)',
                    'ru' => 'Нефтепродукты (Бензин, Дизельное топливо, Керосин, Топливо)',
                    'ar' => 'المنتجات البترولية (بنزين، ديزل، كيروسين، وقود)',
                ],
            ],
            [
                'slug' => 'bitumen-insulation',
                'title' => [
                    'en' => 'Bitumen and Insulation',
                    'fa' => 'قیر و ایزولاسیون',
                    'tr' => 'Bitüm ve Yalıtım',
                    'ru' => 'Битум и изоляция',
                    'ar' => 'القار والعزل',
                ],
            ],
            [
                'slug' => 'lubricants-industrial-oils',
                'title' => [
                    'en' => 'Lubricants and Industrial Oils (Grease, Engine Oil)',
                    'fa' => 'روانکارها و روغن‌های صنعتی (گریس، روغن موتور)',
                    'tr' => 'Yağlayıcılar ve Endüstriyel Yağlar (Gres, Motor Yağı)',
                    'ru' => 'Смазочные материалы и индустриальные масла (Смазка, Моторное масло)',
                    'ar' => 'مواد التشحيم والزيوت الصناعية (شحم، زيت محرك)',
                ],
            ],
            [
                'slug' => 'paraffin-vaseline',
                'title' => [
                    'en' => 'Paraffin and Vaseline',
                    'fa' => 'پارافین و وازلین',
                    'tr' => 'Parafin ve Vazelin',
                    'ru' => 'Парафин и вазелин',
                    'ar' => 'البارافين والفازلين',
                ],
            ],
            [
                'slug' => 'natural-gas-lpg',
                'title' => [
                    'en' => 'Natural Gas and LPG',
                    'fa' => 'گاز طبیعی و LPG',
                    'tr' => 'Doğal Gaz ve LPG',
                    'ru' => 'Природный газ и СУГ (LPG)',
                    'ar' => 'الغاز الطبيعي وغاز البترول المسال',
                ],
            ],
            [
                'slug' => 'basic-petrochemical-products',
                'title' => [
                    'en' => 'Basic Petrochemical Products (Methanol, Urea, Ammonia, Aromatics)',
                    'fa' => 'محصولات پتروشیمی پایه (متانول، اوره، آمونیاک، آروماتیک‌ها)',
                    'tr' => 'Temel Petrokimya Ürünleri (Metanol, Üre, Amonyak, Aromatikler)',
                    'ru' => 'Базовые нефтехимические продукты (Метанол, Мочевина, Аммиак, Ароматические углеводороды)',
                    'ar' => 'المنتجات البتروكيماوية الأساسية (ميثانول، يوريا، أمونيا، عطريات)',
                ],
            ],
            [
                'slug' => 'polymer-raw-materials',
                'title' => [
                    'en' => 'Polymer Raw Materials (Polyethylene, Polypropylene, PVC, PET)',
                    'fa' => 'مواد اولیه پلیمری (پلی‌اتیلن، پلی‌پروپیلن، PVC، PET)',
                    'tr' => 'Polimer Hammaddeleri (Polietilen, Polipropilen, PVC, PET)',
                    'ru' => 'Полимерное сырье (Полиэтилен, Полипропилен, ПВХ, ПЭТ)',
                    'ar' => 'المواد الخام البوليمرية (بولي إيثيلين، بولي بروبيلين، PVC، PET)',
                ],
            ],
            [
                'slug' => 'plastic-products',
                'title' => [
                    'en' => 'Plastic Products (Containers, Films, Polymer Pipes and Fittings)',
                    'fa' => 'محصولات پلاستیکی (ظروف، فیلم، لوله و اتصالات پلیمری)',
                    'tr' => 'Plastik Ürünler (Kaplar, Filmler, Polimer Boru ve Bağlantı Parçaları)',
                    'ru' => 'Пластиковые изделия (Тара, Пленки, Полимерные трубы и фитинги)',
                    'ar' => 'المنتجات البلاستيكية (حاويات، أفلام، أنابيب وتوصيلات بوليمرية)',
                ],
            ],
            [
                'slug' => 'rubber-products',
                'title' => [
                    'en' => 'Rubber and Rubber Products',
                    'fa' => 'لاستیک و محصولات لاستیکی',
                    'tr' => 'Kauçuk ve Kauçuk Ürünleri',
                    'ru' => 'Резина и резиновые изделия',
                    'ar' => 'المطاط والمنتجات المطاطية',
                ],
            ],
            [
                'slug' => 'composites-masterbatch',
                'title' => [
                    'en' => 'Composites and Masterbatch',
                    'fa' => 'کامپوزیت و مستربچ',
                    'tr' => 'Kompozit ve Masterbatch',
                    'ru' => 'Композиты и мастер-батч',
                    'ar' => 'المواد المركبة والماستر باتش',
                ],
            ],
            [
                'slug' => 'resins-adhesives-coating-chemicals',
                'title' => [
                    'en' => 'Resins, Adhesives and Coating Chemicals',
                    'fa' => 'رزین، چسب و مواد شیمیایی پوششی',
                    'tr' => 'Reçineler, Yapıştırıcılar ve Kaplama Kimyasalları',
                    'ru' => 'Смолы, клеи и химические вещества для покрытий',
                    'ar' => 'الراتنجات، المواد اللاصقة والمواد الكيميائية للطلاء',
                ],
            ],
            [
                'slug' => 'carbon-industrial-carbon-black',
                'title' => [
                    'en' => 'Carbon and Industrial Carbon Black',
                    'fa' => 'کربن و دوده صنعتی',
                    'tr' => 'Karbon ve Endüstriyel Kurum',
                    'ru' => 'Углерод и технический углерод (сажа)',
                    'ar' => 'الكربون والسخام الصناعي',
                ],
            ],
            [
                'slug' => 'sulfur-derivatives',
                'title' => [
                    'en' => 'Sulfur and its Derivatives',
                    'fa' => 'گوگرد و مشتقات آن',
                    'tr' => 'Kükürt ve Türevleri',
                    'ru' => 'Сера и ее производные',
                    'ar' => 'الكبريت ومشتقاته',
                ],
            ],
            [
                'slug' => 'oil-gas-engineering-contracting-services',
                'title' => [
                    'en' => 'Oil and Gas Engineering and Contracting Services (EPC)',
                    'fa' => 'خدمات مهندسی و پیمانکاری نفت و گاز (EPC)',
                    'tr' => 'Petrol ve Gaz Mühendislik ve Müteahhitlik Hizmetleri (EPC)',
                    'ru' => 'Инжиниринговые и подрядные услуги в нефтегазовой сфере (EPC)',
                    'ar' => 'خدمات الهندسة والمقاولات في النفط والغاز (EPC)',
                ],
            ],
        ];

        foreach ($oilGasSubCategories as $index => $subCategoryData) {
            CompanyCategory::factory()->create([
                'parent_id' => $oil_gas_petrochemicals_polymers->id,
                'slug' => $subCategoryData['slug'],
                'title' => $subCategoryData['title'],
                'sort_order' => $index + 1,
            ]);
        }

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
