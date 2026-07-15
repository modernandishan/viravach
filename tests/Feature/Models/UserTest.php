<?php

namespace Tests\Feature\Models;

use App\Enums\Gender;
use App\Models\Profile;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StateSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_user_automatically_creates_a_profile(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->profile);
        $this->assertInstanceOf(Profile::class, $user->profile);
        $this->assertSame($user->id, $user->profile->user_id);
    }

    public function test_super_admin_seeder_produces_a_user_with_a_profile(): void
    {
        $this->seed(CountrySeeder::class);
        $this->seed(StateSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $user = User::where('email', 'info@viravach.com')->firstOrFail();

        $this->assertNotNull($user->profile);
        $this->assertSame('mjavad', $user->profile->username);
    }

    public function test_user_can_have_an_avatar_media_with_a_queued_webp_conversion(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $media = $user->addMedia(UploadedFile::fake()->image('avatar.jpg', 10, 10))
            ->toMediaCollection('avatar', 'public');

        $this->assertTrue($user->fresh()->hasMedia('avatar'));
        $this->assertSame($media->id, $user->fresh()->getFirstMedia('avatar')->id);
    }

    public function test_scope_publicly_visible_excludes_deactivated_users(): void
    {
        $active = User::factory()->create();
        $deactivated = User::factory()->create(['deactivated_at' => now()]);

        $result = User::publiclyVisible()->pluck('id');

        $this->assertTrue($result->contains($active->id));
        $this->assertFalse($result->contains($deactivated->id));
    }

    public function test_profile_completion_percentage_increases_as_fields_are_filled(): void
    {
        $user = User::factory()->create();
        $bare = $user->fresh()->profileCompletionPercentage();

        $user->profile()->update([
            'username' => 'someone',
            'national_code' => '1234567890',
            'gender' => Gender::Male,
            'birth_date' => '2000-01-01',
            'city' => 'Isfahan',
        ]);

        $filled = $user->fresh()->profileCompletionPercentage();

        $this->assertGreaterThan($bare, $filled);
        $this->assertLessThanOrEqual(100, $filled);
    }
}
