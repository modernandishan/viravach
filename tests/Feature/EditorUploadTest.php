<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditorUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_route_requires_authentication(): void
    {
        $route = collect(Route::getRoutes())->first(
            fn ($route) => $route->uri() === 'editor/upload'
        );

        $this->assertContains('auth', $route->gatherMiddleware());
    }

    public function test_authenticated_user_can_upload_an_image(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson($this->appUrl('/editor/upload'), [
            'image' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertOk()->assertJsonStructure(['url']);

        Storage::disk('s3')->assertExists(
            'editor/'.$user->id.'/'.basename(parse_url($response->json('url'), PHP_URL_PATH))
        );
    }
}
