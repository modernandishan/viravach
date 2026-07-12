<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        $iran = Country::where('iso2', 'IR')->firstOrFail();

        $type = [
            'fa' => 'استان',
            'en' => 'Province',
            'tr' => 'Eyalet',
            'ru' => 'Провинция',
            'ar' => 'محافظة',
        ];

        $states = [
            ['code' => '01', 'slug' => 'east-azerbaijan', 'name' => ['fa' => 'آذربایجان شرقی', 'en' => 'East Azerbaijan', 'tr' => 'Şərqi Azərbaycan', 'ru' => 'Восточный Азербайджан', 'ar' => 'أذربيجان الشرقية']],
            ['code' => '02', 'slug' => 'west-azerbaijan', 'name' => ['fa' => 'آذربایجان غربی', 'en' => 'West Azerbaijan', 'tr' => 'Qərbi Azərbaycan', 'ru' => 'Западный Азербайджан', 'ar' => 'أذربيجان الغربية']],
            ['code' => '03', 'slug' => 'ardabil', 'name' => ['fa' => 'اردبیل', 'en' => 'Ardabil', 'tr' => 'Ərdəbil', 'ru' => 'Ардабиль', 'ar' => 'أردبيل']],
            ['code' => '04', 'slug' => 'isfahan', 'name' => ['fa' => 'اصفهان', 'en' => 'Isfahan', 'tr' => 'İsfahan', 'ru' => 'Исфахан', 'ar' => 'أصفهان']],
            ['code' => '05', 'slug' => 'alborz', 'name' => ['fa' => 'البرز', 'en' => 'Alborz', 'tr' => 'Əlbərz', 'ru' => 'Альборз', 'ar' => 'ألبرز']],
            ['code' => '06', 'slug' => 'ilam', 'name' => ['fa' => 'ایلام', 'en' => 'Ilam', 'tr' => 'Îlam', 'ru' => 'Илам', 'ar' => 'إيلام']],
            ['code' => '07', 'slug' => 'bushehr', 'name' => ['fa' => 'بوشهر', 'en' => 'Bushehr', 'tr' => 'Buşehr', 'ru' => 'Бушир', 'ar' => 'بوشهر']],
            ['code' => '08', 'slug' => 'tehran', 'name' => ['fa' => 'تهران', 'en' => 'Tehran', 'tr' => 'Tahran', 'ru' => 'Тегеран', 'ar' => 'طهران']],
            ['code' => '09', 'slug' => 'chaharmahal-and-bakhtiari', 'name' => ['fa' => 'چهارمحال و بختیاری', 'en' => 'Chaharmahal and Bakhtiari', 'tr' => 'Çaharmahal və Bəxtiyari', 'ru' => 'Чехармехаль и Бахтиари', 'ar' => 'تشهارمحال وبختياري']],
            ['code' => '10', 'slug' => 'south-khorasan', 'name' => ['fa' => 'خراسان جنوبی', 'en' => 'South Khorasan', 'tr' => 'Cənubi Xorasan', 'ru' => 'Южный Хорасан', 'ar' => 'خراسان الجنوبية']],
            ['code' => '11', 'slug' => 'razavi-khorasan', 'name' => ['fa' => 'خراسان رضوی', 'en' => 'Razavi Khorasan', 'tr' => 'Rəzəvi Xorasan', 'ru' => 'Хорасан-Резави', 'ar' => 'خراسان الرضوية']],
            ['code' => '12', 'slug' => 'north-khorasan', 'name' => ['fa' => 'خراسان شمالی', 'en' => 'North Khorasan', 'tr' => 'Şimali Xorasan', 'ru' => 'Северный Хорасан', 'ar' => 'خراسان الشمالية']],
            ['code' => '13', 'slug' => 'khuzestan', 'name' => ['fa' => 'خوزستان', 'en' => 'Khuzestan', 'tr' => 'Xuzistan', 'ru' => 'Хузестан', 'ar' => 'خوزستان']],
            ['code' => '14', 'slug' => 'zanjan', 'name' => ['fa' => 'زنجان', 'en' => 'Zanjan', 'tr' => 'Zəncan', 'ru' => 'Зенджан', 'ar' => 'زنجان']],
            ['code' => '15', 'slug' => 'semnan', 'name' => ['fa' => 'سمنان', 'en' => 'Semnan', 'tr' => 'Simnan', 'ru' => 'Семнан', 'ar' => 'سمنان']],
            ['code' => '16', 'slug' => 'sistan-and-baluchestan', 'name' => ['fa' => 'سیستان و بلوچستان', 'en' => 'Sistan and Baluchestan', 'tr' => 'Sistan və Bəlucistan', 'ru' => 'Систан и Белуджистан', 'ar' => 'سيستان وبلوشستان']],
            ['code' => '17', 'slug' => 'fars', 'name' => ['fa' => 'فارس', 'en' => 'Fars', 'tr' => 'Fars', 'ru' => 'Фарс', 'ar' => 'فارس']],
            ['code' => '18', 'slug' => 'qazvin', 'name' => ['fa' => 'قزوین', 'en' => 'Qazvin', 'tr' => 'Qəzvin', 'ru' => 'Казвин', 'ar' => 'قزوين']],
            ['code' => '19', 'slug' => 'qom', 'name' => ['fa' => 'قم', 'en' => 'Qom', 'tr' => 'Qum', 'ru' => 'Кум', 'ar' => 'قم']],
            ['code' => '20', 'slug' => 'kurdistan', 'name' => ['fa' => 'کردستان', 'en' => 'Kurdistan', 'tr' => 'Kürdistan', 'ru' => 'Курдистан', 'ar' => 'كردستان']],
            ['code' => '21', 'slug' => 'kerman', 'name' => ['fa' => 'کرمان', 'en' => 'Kerman', 'tr' => 'Kirman', 'ru' => 'Керман', 'ar' => 'كرمان']],
            ['code' => '22', 'slug' => 'kermanshah', 'name' => ['fa' => 'کرمانشاه', 'en' => 'Kermanshah', 'tr' => 'Kirmanşah', 'ru' => 'Керманшах', 'ar' => 'كرمانشاه']],
            ['code' => '23', 'slug' => 'kohgiluyeh-and-boyer-ahmad', 'name' => ['fa' => 'کهگیلویه و بویراحمد', 'en' => 'Kohgiluyeh and Boyer-Ahmad', 'tr' => 'Kohgiluyə və Boyer-Əhməd', 'ru' => 'Кохгилуйе и Бойерахмед', 'ar' => 'كهكيلويه وبوير أحمد']],
            ['code' => '24', 'slug' => 'golestan', 'name' => ['fa' => 'گلستان', 'en' => 'Golestan', 'tr' => 'Gülüstan', 'ru' => 'Голестан', 'ar' => 'غولستان']],
            ['code' => '25', 'slug' => 'gilan', 'name' => ['fa' => 'گیلان', 'en' => 'Gilan', 'tr' => 'Gilan', 'ru' => 'Гилян', 'ar' => 'غيلان']],
            ['code' => '26', 'slug' => 'lorestan', 'name' => ['fa' => 'لرستان', 'en' => 'Lorestan', 'tr' => 'Luristan', 'ru' => 'Лурестан', 'ar' => 'لرستان']],
            ['code' => '27', 'slug' => 'mazandaran', 'name' => ['fa' => 'مازندران', 'en' => 'Mazandaran', 'tr' => 'Mazandaran', 'ru' => 'Мазандаран', 'ar' => 'مازندران']],
            ['code' => '28', 'slug' => 'markazi', 'name' => ['fa' => 'مرکزی', 'en' => 'Markazi', 'tr' => 'Mərəkəzi', 'ru' => 'Маркази', 'ar' => 'المركزية']],
            ['code' => '29', 'slug' => 'hormozgan', 'name' => ['fa' => 'هرمزگان', 'en' => 'Hormozgan', 'tr' => 'Hormozgan', 'ru' => 'Хормозган', 'ar' => 'هرمزغان']],
            ['code' => '30', 'slug' => 'hamadan', 'name' => ['fa' => 'همدان', 'en' => 'Hamadan', 'tr' => 'Həmədan', 'ru' => 'Хамадан', 'ar' => 'همدان']],
            ['code' => '31', 'slug' => 'yazd', 'name' => ['fa' => 'یزد', 'en' => 'Yazd', 'tr' => 'Yəzd', 'ru' => 'Йезд', 'ar' => 'يزد']],
        ];

        foreach ($states as $state) {
            State::updateOrCreate(
                [
                    'country_id' => $iran->id,
                    'code' => $state['code'],
                ],
                [
                    'name' => $state['name'],
                    'slug' => $state['slug'],
                    'type' => $type,
                    'is_active' => true,
                ]
            );
        }
    }
}
