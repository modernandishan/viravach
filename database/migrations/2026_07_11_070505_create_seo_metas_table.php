<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();

            // ارتباط چندریختی: این رکورد سئو به کدام مدل (Company، CompanyCategory، ...) تعلق دارد
            $table->morphs('seoable'); // seoable_id + seoable_type + ایندکس ترکیبی

            // --- Meta Tags پایه (چندزبانه - JSON) ---
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();   // آرایه‌ی کلیدواژه به ازای هر زبان
            $table->json('focus_keyword')->nullable();   // کلیدواژه‌ی هدف صفحه، برای تحلیل درون‌صفحه‌ای

            // --- Robots / Indexing (سراسری، غیرزبانه) ---
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
            $table->string('canonical_url')->nullable();

            // --- Open Graph ---
            $table->string('og_type')->default('website');   // website, article, product, profile...
            $table->json('og_title')->nullable();             // خالی = fallback به meta_title
            $table->json('og_description')->nullable();       // خالی = fallback به meta_description
            $table->string('og_image')->nullable();           // مستقل از logo/featured_image شرکت

            // --- Twitter Card ---
            $table->string('twitter_card_type')->default('summary_large_image');
            $table->json('twitter_title')->nullable();
            $table->json('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();

            // --- JSON-LD / Structured Data ---
            $table->string('schema_type')->nullable();  // Organization, LocalBusiness, Product, Article...
            $table->json('schema_extra')->nullable();    // فیلدهای آزاد ساختاریافته (غیرزبانه)

            // --- Sitemap ---
            $table->boolean('sitemap_include')->default(true);
            $table->decimal('sitemap_priority', 2, 1)->default(0.5); // بازه‌ی 0.0 تا 1.0
            $table->string('sitemap_change_freq')->default('weekly'); // always/hourly/daily/weekly/monthly/yearly/never

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_metas');
    }
};
