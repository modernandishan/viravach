<?php

namespace Database\Seeders;

use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\CompanyCategory;
use App\Models\State;
use App\Models\User;
use App\Services\CompanyPublicationService;
use App\Services\CompanySubscriptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Dev-only company seeder including MinIO-backed media.
 *
 * Deliberately does not use WithoutModelEvents: MediaLibrary relies on model
 * events for conversion dispatching, and the subscription slug generator
 * needs them too.
 *
 * Run with: php artisan db:seed --class=CompanyMediaDemoSeeder
 */
class CompanyMediaDemoSeeder extends Seeder
{
    private const ASSETS = __DIR__.'/assets/companies';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('CompanyMediaDemoSeeder must not be run in production.');

            return;
        }

        $this->assertStorageReachable();

        // registerMediaConversions() marks the webp conversion as queued().
        // Forcing the sync connection makes PerformConversionsJob run inline,
        // so getFirstMediaUrl('logo', 'webp') resolves the moment the seeder
        // finishes instead of waiting for a worker that may not be running.
        config(['queue.default' => 'sync']);

        $this->callPrerequisites();

        $user = User::updateOrCreate(
            ['email' => 'seed@viravach.com'],
            [
                'name' => ['fa' => 'کاربر', 'en' => 'Seed'],
                'family' => ['fa' => 'نمونه', 'en' => 'User'],
                'phone' => '09120000001',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        $categories = CompanyCategory::query()->whereNotNull('parent_id')->inRandomOrder()->limit(20)->get();
        $states = State::query()->inRandomOrder()->limit(20)->get();

        $logos = $this->assetFiles('logos');
        $gallery = $this->assetFiles('gallery');

        foreach (range(1, 10) as $index) {
            $company = Company::factory()->for($user)->approved()->create([
                'slug' => "seed-company-{$index}",
                'is_verified' => true,
                'is_featured' => $index <= 3,
            ]);

            $company->categories()->sync($categories->random(2)->pluck('id'));

            $state = $states->random();

            CompanyAddress::factory()->for($company)->create([
                'country_id' => $state->country_id,
                'state_id' => $state->id,
                'is_primary' => true,
            ]);

            // Media must exist on the draft before publishing: the snapshot
            // copies whatever collections the company holds at that moment.
            $this->attachMedia($company, $logos[$index % count($logos)], collect($gallery)->random(3)->all());

            app(CompanySubscriptionService::class)->assignFreePlanIfMissing($company);
            app(CompanyPublicationService::class)->publish($company);

            $this->command?->info("Seeded company #{$index} with media.");
        }
    }

    /**
     * preservingOriginal() is mandatory here: without it MediaLibrary moves
     * the source file out of the repository, making the seeder single-use.
     */
    private function attachMedia(Company $company, string $logo, array $gallery): void
    {
        $company->addMedia(self::ASSETS."/logos/{$logo}")
            ->preservingOriginal()
            ->usingFileName("{$company->slug}-logo.png")
            ->toMediaCollection('logo', 's3');

        foreach ($gallery as $position => $file) {
            $company->addMedia(self::ASSETS."/gallery/{$file}")
                ->preservingOriginal()
                ->usingFileName("{$company->slug}-gallery-{$position}.jpg")
                ->toMediaCollection('gallery', 's3');
        }
    }

    /**
     * Fail fast with an actionable message instead of letting Flysystem
     * swallow the error (config/filesystems.php sets 'throw' => false on s3).
     */
    private function assertStorageReachable(): void
    {
        try {
            Storage::disk('s3')->put('.seeder-healthcheck', 'ok');
            $ok = Storage::disk('s3')->get('.seeder-healthcheck') === 'ok';
            Storage::disk('s3')->delete('.seeder-healthcheck');
        } catch (\Throwable $e) {
            throw new RuntimeException('MinIO unreachable: '.$e->getMessage(), previous: $e);
        }

        if (! $ok) {
            throw new RuntimeException('MinIO write succeeded but read-back failed. Check AWS_BUCKET and credentials.');
        }
    }

    /** @return list<string> */
    private function assetFiles(string $directory): array
    {
        $files = array_values(array_diff(scandir(self::ASSETS."/{$directory}"), ['.', '..', '.gitkeep']));

        if ($files === []) {
            throw new RuntimeException("No seed assets found in {$directory}.");
        }

        return $files;
    }

    private function callPrerequisites(): void
    {
        if (State::query()->doesntExist()) {
            $this->call(CountrySeeder::class);
            $this->call(StateSeeder::class);
        }

        if (CompanyCategory::query()->doesntExist()) {
            $this->call(CompanyCategorySeeder::class);
        }
    }
}
