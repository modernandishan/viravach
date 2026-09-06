<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ManageWordPressContentSettings;
use App\Models\User;
use App\Settings\WordPressContentSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WordPressContentSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_an_admin_can_open_and_save_the_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ManageWordPressContentSettings::class)
            ->assertSuccessful()
            // Independent kill-switch, distinct wording from the profile
            // pipeline's generic toggle.
            ->assertSee('فعال‌سازی تولید خودکار مقاله وردپرس')
            ->fillForm(['enabled' => true])
            ->call('save')
            ->assertSuccessful();

        $this->assertTrue(app(WordPressContentSettings::class)->enabled);
    }

    public function test_a_regular_user_cannot_access_the_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(ManageWordPressContentSettings::getUrl())
            ->assertForbidden();
    }
}
