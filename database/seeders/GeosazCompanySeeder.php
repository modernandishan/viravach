<?php

namespace Database\Seeders;

use App\Enums\CompanyReviewStatus;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\CompanyCategory;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Services\CompanyPublicationService;
use App\Services\CompanySubscriptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Registers the real company "Geosaz Industrial Group" (geosaz.com) into
 * Viravach, in all five site locales. Safe to run repeatedly and safe in
 * production — everything is keyed via firstOrCreate/updateOrCreate.
 *
 * Deliberately does not use WithoutModelEvents: MediaLibrary conversions,
 * the subscription slug generator and CompanyObserver all rely on model
 * events to fire.
 */
class GeosazCompanySeeder extends Seeder
{
    private const ASSET_PATH = __DIR__.'/assets/companies/geosaz';

    public function run(): void
    {
        // registerMediaConversions() marks the webp conversion as ->queued();
        // force it to run inline so getFirstMediaUrl(..., 'webp') resolves
        // immediately after this seeder finishes.
        config(['queue.default' => 'sync']);

        $this->assertS3DiskIsWritable();

        $user = $this->createOwnerUser();
        $company = $this->createCompany($user);

        $this->syncCategories($company);
        $this->syncAddress($company);
        $this->syncExportCountries($company);
        $this->syncMedia($company);
        $this->syncSeoMeta($company);

        // Media must never be attached inside a DB transaction — publish()
        // opens its own, so this must be the very last step.
        app(CompanySubscriptionService::class)->assignFreePlanIfMissing($company);
        app(CompanyPublicationService::class)->publish($company);

        $this->command?->info('Geosaz Industrial Group seeded: '.$company->slug);
    }

    /**
     * config/filesystems.php sets 'throw' => false on the s3 disk, so a
     * misconfigured MinIO/S3 connection would otherwise fail media uploads
     * silently. Fail loudly up front instead.
     */
    private function assertS3DiskIsWritable(): void
    {
        $disk = Storage::disk('s3');
        $healthCheckPath = '.geosaz-healthcheck';

        $written = $disk->put($healthCheckPath, (string) now()->timestamp);
        $readBack = $written ? $disk->get($healthCheckPath) : null;
        $disk->delete($healthCheckPath);

        if (! $written || $readBack === null) {
            throw new \RuntimeException(
                'GeosazCompanySeeder: the "s3" disk failed a write/read health check. '.
                'Check AWS_*/MinIO filesystem configuration before seeding media.',
            );
        }
    }

    private function createOwnerUser(): User
    {
        $user = User::firstOrCreate(
            ['phone' => '09133600896'],
            [
                'name' => [
                    'fa' => 'مصطفی',
                    'en' => 'Mostafa',
                    'ar' => 'مصطفى',
                    'ru' => 'Мостафа',
                    'tr' => 'Mostafa',
                ],
                'family' => [
                    'fa' => 'شفیعی',
                    'en' => 'Shafiei',
                    'ar' => 'شفيعي',
                    'ru' => 'Шафии',
                    'tr' => 'Şefii',
                ],
                'email' => 'info@geosaz.com',
                'email_verified_at' => now(),
                'password' => Hash::make(Str::password(32)),
            ],
        );

        if ($user->wasRecentlyCreated) {
            $user->assignRole('user');
        }

        return $user;
    }

    private function createCompany(User $user): Company
    {
        return Company::updateOrCreate(
            ['slug' => 'geosaz'],
            [
                'user_id' => $user->id,
                'legal_type' => null, // TODO(jay): confirm registered legal form
                'registration_number' => null, // TODO(jay): confirm with client
                'national_id' => null, // TODO(jay): confirm with client
                'established_at' => null, // TODO(jay): confirm exact founding date
                'employee_range' => null, // TODO(jay): confirm headcount band
                'website' => 'https://geosaz.com',
                'email' => 'info@geosaz.com',
                'phones' => ['09133600896'],
                'social_links' => [
                    'instagram' => 'https://www.instagram.com/fanavaransabz.shafiei/',
                    'whatsapp' => 'https://wa.me/989133600986',
                    'website' => 'https://geosaz.com',
                ],
                'review_status' => CompanyReviewStatus::Approved,
                'reviewed_at' => now(),
                'is_verified' => true,
                'is_featured' => true,
                'name' => [
                    'fa' => 'گروه صنعتی ژئوساز',
                    'en' => 'Geosaz Industrial Group',
                    'ar' => 'مجموعة جيوساز الصناعية',
                    'ru' => 'Промышленная группа «Геосаз»',
                    'tr' => 'Geosaz Endüstri Grubu',
                ],
                'legal_name' => [
                    'fa' => 'گروه صنعتی عایق گستر میراب',
                    'en' => 'Ayegh Gostar Mirab Industrial Group',
                    'ar' => 'مجموعة عايق غستر ميراب الصناعية',
                    'ru' => 'Промышленная группа «Аег Гостар Мираб»',
                    'tr' => 'Ayegh Gostar Mirab Endüstri Grubu',
                ],
                'summary' => $this->summaryTranslations(),
                'brief' => 'گروه صنعتی ژئوساز با بیش از ۲۰ سال سابقه اجرایی، تولیدکننده مستقیم ورق ژئوممبران HDPE، ژئوتکستایل و ژئوگرید در ایران است. این مجموعه علاوه بر تولید، تأمین لوله و اتصالات پلی‌اتیلن، دستگاه‌های جوش و متعلقات اجرایی پروژه‌های عایق‌بندی را نیز بر عهده دارد. ژئوساز چرخه کامل خدمات را از مشاوره فنی رایگان پیش از اجرا، طراحی راهکار اختصاصی، تأمین مصالح، اجرا و نظارت تا خدمات پس از فروش پوشش می‌دهد. تاکنون بیش از ۱۴۰ طرح موفق در ایران و کشورهای حاشیه خلیج فارس اجرا شده است. کاربردهای اصلی محصولات: عایق‌بندی استخرهای ذخیره آب کشاورزی، حوضچه‌های صنعتی و اسیدی، لندفیل و مدیریت پسماند، آبخیزداری، شیلات و پروژه‌های زیست‌محیطی. محصولات دارای گواهینامه‌های ISO 9001، ISO 14001، ISO 45001، CE، UV Resistant و استاندارد ملی ایران بوده و با ضمانت ۱۰ تا ۱۵ ساله عرضه می‌شوند.',
                'brief_locale' => 'fa',
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    private function summaryTranslations(): array
    {
        return [
            'fa' => 'تولیدکننده مستقیم ژئوممبران HDPE، ژئوتکستایل و ژئوگرید در ایران با گواهینامه‌های ISO 9001، ISO 14001 و CE و بیش از ۲۰ سال سابقه اجرایی.',
            'en' => 'Direct Iranian manufacturer of HDPE geomembrane, geotextile and geogrid, certified to ISO 9001, ISO 14001 and CE, with over 20 years of field experience.',
            'ar' => 'مصنّع إيراني مباشر لأغشية الجيوممبرين HDPE والجيوتكستايل والجيوجريد، حاصل على شهادات ISO 9001 وISO 14001 وCE، بخبرة تنفيذية تتجاوز 20 عاماً.',
            'ru' => 'Иранский производитель геомембраны HDPE, геотекстиля и геосетки с сертификатами ISO 9001, ISO 14001 и CE и более чем 20-летним опытом монтажа.',
            'tr' => 'HDPE jeomembran, jeotekstil ve jeogrid üreticisi; ISO 9001, ISO 14001 ve CE sertifikalı, 20 yılı aşkın saha deneyimiyle İran\'dan doğrudan üretim.',
        ];
    }

    private function syncCategories(Company $company): void
    {
        $ids = CompanyCategory::query()
            ->whereIn('slug', [
                'plastic-products',
                'mining-metals-stones-building-materials',
                'industrial-machinery-production-lines',
            ])
            ->pluck('id');

        $company->categories()->sync($ids);
    }

    private function syncAddress(Company $company): void
    {
        $state = State::where('slug', 'isfahan')->first();

        if ($state === null) {
            throw new \RuntimeException(
                'GeosazCompanySeeder: State "isfahan" not found — run StateSeeder first.',
            );
        }

        $city = City::where('slug', 'isfahan')->first();

        CompanyAddress::updateOrCreate(
            ['company_id' => $company->id, 'is_primary' => true],
            [
                'country_id' => $state->country_id,
                'state_id' => $state->id,
                'city_id' => $city?->id,
                'type' => 'office',
                'postal_code' => null, // TODO(jay): confirm with client
                'latitude' => 32.6580,
                'longitude' => 51.7000,
                'address_line' => [
                    'fa' => 'اصفهان، خیابان جی، سه‌راه شهید رجائی، ساختمان بهار، واحد ۱۱',
                    'en' => 'Unit 11, Bahar Building, Shahid Rajaei Junction, Jey St., Isfahan, Iran',
                    'ar' => 'إصفهان، شارع جي، تقاطع الشهيد رجائي، مبنى بهار، الوحدة 11',
                    'ru' => 'Иран, Исфахан, ул. Джей, перекрёсток Шахид Раджаи, здание «Бахар», офис 11',
                    'tr' => 'İsfahan, Jey Caddesi, Şehit Recai Kavşağı, Bahar Binası, No: 11, İran',
                ],
            ],
        );
    }

    private function syncExportCountries(Company $company): void
    {
        $ids = Country::whereIn('iso2', ['IQ', 'AE', 'OM', 'QA', 'KW', 'BH', 'SA', 'AF', 'TM'])->pluck('id');

        $company->exportCountries()->sync($ids);
    }

    private function syncMedia(Company $company): void
    {
        $this->syncSingleMedia($company, self::ASSET_PATH.'/logo.png', 'logo');
        $this->syncSingleMedia($company, self::ASSET_PATH.'/featured.jpg', 'featured_image');

        $this->syncMediaCollection($company, 'gallery', array_map(
            fn (string $file): string => self::ASSET_PATH.'/gallery/'.$file,
            ['01.jpg', '02.jpg', '03.jpg', '04.jpg', '05.jpg', '06.jpg'],
        ));

        $this->syncMediaCollection($company, 'certificates', array_map(
            fn (string $file): string => self::ASSET_PATH.'/certificates/'.$file,
            [
                'iso-9001.jpg',
                'iso-14001.jpg',
                'iso-45001.jpg',
                'ce.jpg',
                'uv-resistant.jpg',
                'iranian-national-standard.jpg',
                'acid-cyanide-safe.jpg',
            ],
        ));
    }

    private function syncSingleMedia(Company $company, string $path, string $collection): void
    {
        $company->clearMediaCollection($collection);

        if (! is_file($path)) {
            return;
        }

        $company->addMedia($path)
            ->preservingOriginal()
            ->toMediaCollection($collection, 's3');
    }

    /**
     * @param  list<string>  $paths
     */
    private function syncMediaCollection(Company $company, string $collection, array $paths): void
    {
        $company->clearMediaCollection($collection);

        foreach ($paths as $path) {
            if (! is_file($path)) {
                continue;
            }

            $company->addMedia($path)
                ->preservingOriginal()
                ->toMediaCollection($collection, 's3');
        }
    }

    private function syncSeoMeta(Company $company): void
    {
        $company->seo()->updateOrCreate([], [
            'meta_title' => [
                'fa' => 'گروه صنعتی ژئوساز | تولیدکننده ژئوممبران، ژئوتکستایل و ژئوگرید',
                'en' => 'Geosaz Industrial Group | HDPE Geomembrane, Geotextile & Geogrid Manufacturer',
                'ar' => 'مجموعة جيوساز الصناعية | مصنّع الجيوممبرين والجيوتكستايل والجيوجريد',
                'ru' => 'Промышленная группа «Геосаз» | Производитель геомембраны и геотекстиля',
                'tr' => 'Geosaz Endüstri Grubu | Jeomembran, Jeotekstil ve Jeogrid Üreticisi',
            ],
            'meta_description' => $this->summaryTranslations(),
            'is_cornerstone' => true,
        ]);
    }
}
