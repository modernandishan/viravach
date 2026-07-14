<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTranslatableNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_and_family_are_stored_per_locale(): void
    {
        $user = User::factory()->create([
            'name' => ['en' => 'John', 'fa' => 'جان'],
            'family' => ['en' => 'Doe', 'fa' => 'دو'],
        ]);

        $this->assertSame('John', $user->getTranslation('name', 'en'));
        $this->assertSame('جان', $user->getTranslation('name', 'fa'));
        $this->assertSame('Doe', $user->getTranslation('family', 'en'));
        $this->assertSame('دو', $user->getTranslation('family', 'fa'));
    }

    public function test_filament_name_is_always_persian_regardless_of_current_locale(): void
    {
        $user = User::factory()->create([
            'name' => ['en' => 'John', 'fa' => 'جان'],
            'family' => ['en' => 'Doe', 'fa' => 'دو'],
        ]);

        app()->setLocale('en');

        $this->assertSame('جان دو', $user->getFilamentName());
    }

    public function test_admin_panel_topbar_shows_the_persian_name(): void
    {
        $user = User::factory()->create([
            'name' => ['en' => 'John', 'fa' => 'جان'],
            'family' => ['en' => 'Doe', 'fa' => 'دو'],
        ]);
        $user->assignRole('super_admin');

        app()->setLocale('en');

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('جان دو');
        $response->assertDontSee('John Doe');
    }
}
