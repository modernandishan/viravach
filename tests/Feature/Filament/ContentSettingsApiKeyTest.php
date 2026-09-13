<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ManageContentSettings;
use App\Models\User;
use App\Settings\ContentSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The stored api_key must never reach the browser, so the form always
 * renders it blank — which makes "submitted blank" mean "keep the current
 * key" rather than "clear the key". These two tests pin both halves of that
 * bargain, since getting either wrong silently destroys a working key.
 */
class ContentSettingsApiKeyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        app(ContentSettings::class)->fill([
            'base_url' => 'https://ai.example/api',
            'api_key' => 'existing-secret-key',
            'model' => 'content-model',
            'translation_model' => 'translation-model',
        ])->save();
    }

    private function adminUser(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    /**
     * The whole form is validated on save, so every other required field has
     * to carry a valid value — only api_key is deliberately left blank.
     *
     * @return array<string, mixed>
     */
    private function formData(string $apiKey): array
    {
        return [
            'enabled' => true,
            'provider' => 'openwebui',
            'base_url' => 'https://ai.example/api',
            'api_key' => $apiKey,
            'model' => 'content-model',
            'translation_model' => 'translation-model',
            'temperature' => 0.5,
            'timeout' => 120,
            'max_retries' => 2,
            'image_enabled' => false,
            'image_model' => 'image-model',
            'image_size' => '1024x1024',
        ];
    }

    public function test_a_blank_api_key_submission_preserves_the_stored_key(): void
    {
        Livewire::actingAs($this->adminUser())
            ->test(ManageContentSettings::class)
            ->assertSuccessful()
            // The decrypted key must not be hydrated into the component's
            // client-side snapshot, which is what assertSet inspects.
            ->assertSet('data.api_key', '')
            ->fillForm($this->formData(''))
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('existing-secret-key', app(ContentSettings::class)->api_key);
    }

    public function test_a_non_blank_api_key_submission_replaces_the_stored_key(): void
    {
        Livewire::actingAs($this->adminUser())
            ->test(ManageContentSettings::class)
            ->fillForm($this->formData('rotated-secret-key'))
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('rotated-secret-key', app(ContentSettings::class)->api_key);
    }
}
