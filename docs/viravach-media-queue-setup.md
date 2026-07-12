# گزارش راه‌اندازی Media Library، MinIO و صف پردازش — پروژه ViraWatch

این سند فرآیند کامل پیاده‌سازی سیستم مدیریت رسانه (تصاویر با متادیتای چندزبانه)، ذخیره‌سازی روی MinIO و راه‌اندازی صف پردازش (Queue) را برای مدل `CompanyCategory` مستند می‌کند.

---

## ۱. هدف

ذخیره‌سازی تصاویر دسته‌بندی شرکت‌ها (`featured_image` و `logo`) با قابلیت:

- نگهداری متادیتای چندزبانه (alt، caption به ازای هر زبان) برای سئوی تصاویر
- تولید خودکار نسخه‌ی بهینه WebP
- ذخیره‌سازی روی MinIO به‌جای دیسک محلی
- اجرای conversion در پس‌زمینه از طریق صف Redis

---

## ۲. پکیج‌های نصب‌شده

```bash
docker exec -it viravach_app composer require \
    spatie/laravel-medialibrary \
    filament/spatie-laravel-media-library-plugin
```

| پکیج | نقش |
|------|-----|
| `spatie/laravel-medialibrary` | هسته‌ی اصلی: ذخیره‌سازی، جدول `media`، متادیتا، conversions |
| `filament/spatie-laravel-media-library-plugin` | فقط UI: کامپوننت آپلود در فرم‌های Filament |

> این دو مکمل یکدیگرند، نه جایگزین. پلاگین Filament بدون پکیج اصلی کار نمی‌کند.

انتشار و اجرای migration جدول `media`:

```bash
docker exec -it viravach_app php artisan vendor:publish --tag="medialibrary-migrations"
docker exec -it viravach_app php artisan migrate
```

---

## ۳. مدل `CompanyCategory`

مسیر: `app/Models/CompanyCategory.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

#[Fillable([
    'parent_id',
    'slug',
    'title',
    'description',
    'sort_order',
    'is_active',
])]
class CompanyCategory extends Model implements HasMedia
{
    use HasFactory,
        SoftDeletes,
        HasTranslations,
        HasRecursiveRelationships,
        InteractsWithMedia;

    public array $translatable = ['title', 'description'];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')->singleFile();
        $this->addMediaCollection('logo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
             ->format('webp')
             ->queued();
    }
}
```

### تصمیمات کلیدی مدل

- **بدون محدودیت اندازه‌ی تصویر:** چون تصاویر توسط ادمین بارگذاری می‌شوند، کیفیت اصلی حفظ می‌شود و فقط فرمت به WebP بهینه‌سازی می‌شود.
- **`->queued()`:** conversion به صف سپرده می‌شود تا آپلود در پنل ادمین سریع بماند.
- **`slug` فقط انگلیسی:** به‌صورت عمدی از `translatable` خارج است؛ slug غیرانگلیسی برای سئو نامناسب است و توسط ادمین دستی وارد می‌شود.
- **ساختار درختی:** از `staudenmeir/laravel-adjacency-list` (Recursive CTE) برای عمق نامحدود دسته‌بندی استفاده شده است.

---

## ۴. Migration جدول دسته‌بندی

مسیر: `database/migrations/xxxx_create_company_categories_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('company_categories')
                  ->nullOnDelete();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_categories');
    }
};
```

> فیلدهای `featured_image` و `logo` از جدول حذف شدند؛ MediaLibrary همه‌ی فایل‌ها را در جدول `media` خودش نگهداری می‌کند.

---

## ۵. پیکربندی MinIO در `.env`

```env
FILESYSTEM_DISK=s3
MEDIA_DISK=s3

AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=<مقدار minio_root_password.txt>
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=viravach-media
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

چون کانتینرهای `viravach_app` و `minio` روی شبکه‌ی مشترک `webproxy` قرار دارند، آدرس داخلی `http://minio:9000` مستقیم قابل دسترسی است.

---

## ۶. ساخت Bucket

```bash
docker exec -it minio mc alias set local http://localhost:9000 \
    $(sudo cat /HHD/docker/secrets/minio_root_user.txt) \
    $(sudo cat /HHD/docker/secrets/minio_root_password.txt)

docker exec -it minio mc mb local/viravach-media
docker exec -it minio mc anonymous set download local/viravach-media
```

`anonymous set download` اجازه‌ی خواندن عمومی فایل‌ها را می‌دهد تا تصاویر مستقیم در سایت نمایش داده شوند.

---

## ۷. پیکربندی CORS — نکته‌ی مهم

### مشکل

دستور `mc cors set` در سطح bucket با خطای زیر شکست خورد:

```
A header you provided implies functionality that is not implemented.
```

### علت

**CORS در سطح bucket فقط در نسخه‌ی تجاری MinIO (AIStor) پشتیبانی می‌شود، نه در نسخه‌ی community.** فایل‌های `cors.json` و `cors.xml` در نسخه‌ی رایگان بی‌اثرند.

### راه‌حل صحیح

استفاده از تنظیم CORS در سطح کل سرور از طریق متغیر محیطی `MINIO_API_CORS_ALLOW_ORIGIN`.

اصلاح `docker-compose.yml` سرویس MinIO (مسیر `/HHD/docker/minio/`):

```yaml
services:
  minio:
    image: minio/minio:latest
    container_name: minio
    restart: always
    command: server /data --console-address ":9001"
    environment:
      MINIO_ROOT_USER_FILE: /run/secrets/minio_root_user
      MINIO_ROOT_PASSWORD_FILE: /run/secrets/minio_root_password
      MINIO_SERVER_URL: http://localhost:9000
      MINIO_BROWSER_REDIRECT_URL: https://minio-console.hktp.ir
      MINIO_API_CORS_ALLOW_ORIGIN: "https://viravach.com,https://modernandishan.ir"
    volumes:
      - /HHD/docker/minio/data:/data
    networks:
      - webproxy
    secrets:
      - minio_root_user
      - minio_root_password
```

اعمال:

```bash
cd /HHD/docker/minio
docker compose up -d
```

> محدودیت: این متغیر الگوی wildcard دامنه (`*.viravach.com`) را نمی‌پذیرد؛ فقط دامنه‌های دقیق (با کاما جدا شده) یا `*` کامل. برای این پروژه دو دامنه‌ی دقیق کافی است.

---

## ۸. راه‌اندازی صف پردازش (Queue Worker)

در `.env` از قبل `QUEUE_CONNECTION=redis` تنظیم شده بود. تنها یک سرویس worker به Docker Compose نیاز بود.

افزودن سرویس به `docker-compose.yml` پروژه (مسیر `/HHD/docker/viravach/`):

```yaml
  queue-worker:
    build:
      context: .
      dockerfile: Dockerfile
      args:
        UID: 1000
        GID: 1000
    container_name: viravach_queue
    restart: always
    user: "1000:1000"
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    volumes:
      - /var/www/viravach:/var/www/html
    networks:
      - webproxy
    environment:
      - DB_HOST=postgres
      - DB_PORT=5432
      - DB_DATABASE=viravach_laravel
      - DB_USERNAME=viravach_user
      - DB_PASSWORD=${DB_PASSWORD}
      - REDIS_HOST=redis
      - REDIS_PASSWORD=${REDIS_PASSWORD}
      - REDIS_PORT=6379
    depends_on:
      - app
```

### توضیح پارامترهای worker

| پارامتر | دلیل |
|---------|------|
| همان build و Dockerfile اپ | worker به همان محیط PHP و کدبیس نیاز دارد |
| `--max-time=3600` | restart هر یک ساعت برای جلوگیری از memory leak |
| `--tries=3` | هر job تا ۳ بار در صورت شکست retry می‌شود |
| `--sleep=3` | فاصله‌ی ۳ ثانیه‌ای بین بررسی صف در زمان خالی بودن |
| `depends_on: app` | بالا آمدن worker پس از آماده شدن اپ |

اجرا:

```bash
cd /HHD/docker/viravach
docker compose up -d queue-worker
```

---

## ۹. تست و تأیید

بررسی وضعیت worker:

```bash
docker ps --filter "name=viravach_queue"
```

ارسال یک job آزمایشی:

```bash
docker exec -it viravach_app php artisan tinker \
    --execute="dispatch(function () { \Log::info('Queue works!'); });"
```

مشاهده‌ی لاگ worker:

```bash
docker logs --tail 20 viravach_queue
```

مشاهده‌ی پیام `Queue works!` در لاگ به معنای عملیاتی بودن کامل صف است.

---

## ۱۰. مقیاس‌پذیری

برای افزایش تعداد worker در آینده (هنگام افزایش حجم job):

```bash
docker compose up -d --scale queue-worker=3
```

> برای این کار باید `container_name` از سرویس worker حذف شود، چون نام تکراری مجاز نیست.

---

## جمع‌بندی نکات کلیدی

1. **MediaLibrary + پلاگین Filament مکمل‌اند** و باید هر دو نصب شوند.
2. **CORS سطح bucket در MinIO رایگان پشتیبانی نمی‌شود** — راه‌حل، متغیر سطح سرور `MINIO_API_CORS_ALLOW_ORIGIN` است.
3. **متادیتای چندزبانه‌ی تصاویر** در `custom_properties` جدول `media` (JSON) ذخیره می‌شود و برای سئو حیاتی است.
4. **conversion صف‌محور** آپلود ادمین را سریع نگه می‌دارد.
5. **slug فقط انگلیسی** برای سئوی مناسب.
6. ساختار درختی با **Recursive CTE** به‌جای Closure Table — استاندارد به‌روز با عمق نامحدود.
