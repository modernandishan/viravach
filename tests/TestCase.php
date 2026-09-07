<?php

namespace Tests;

use App\Observers\UserObserver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    /**
     * Host-aware request helpers. Routes are split per hostname in
     * bootstrap/app.php, so requests must carry the right Host header or they
     * miss their routes (or hit the legacy-host redirects). Pass the full URL
     * returned here to get()/postJson()/etc.
     */
    protected function publicUrl(string $path = '/'): string
    {
        return 'https://'.config('domains.public').$path;
    }

    protected function appUrl(string $path = '/'): string
    {
        return 'https://'.config('domains.app').$path;
    }

    protected function adminUrl(string $path = '/'): string
    {
        return 'https://'.config('domains.admin').$path;
    }

    /**
     * Roles are assigned to every user via {@see UserObserver}, so any
     * test that refreshes the database needs them seeded before creating users.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Every test gets a fake 's3' disk, whether or not it thinks it
        // touches media. MEDIA_DISK=s3, so medialibrary's default disk is the
        // production MinIO bucket, and a test that writes media without this
        // leaves a real object behind there — permanently, because the test
        // database is in-memory sqlite and the row that pointed at the file
        // never survives the run. Two tests were doing exactly that
        // (MediaResourceTest, WordPressPostImageJobTest).
        //
        // Faking the disk is what fixes this rather than pointing MEDIA_DISK
        // at another value in phpunit.xml: several call sites name the 's3'
        // disk explicitly — GenerateWordPressPostImage,
        // CompanyPublicationService::copyMedia(), the dashboard's
        // saveIntroVideo() — and would keep writing to the real bucket. It
        // also keeps the existing assertSame('s3', $media->disk) and
        // Storage::disk('s3')->assertExists() assertions meaningful.
        Storage::fake('s3');

        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            $this->seed(RoleSeeder::class);
        }
    }
}
