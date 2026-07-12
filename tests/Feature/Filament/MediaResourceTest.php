<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\CompanyCategory;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class MediaResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->actingAs(User::factory()->create());
    }

    public function test_it_can_list_media(): void
    {
        $media = $this->createMedia();

        Livewire::test(ListMedia::class)
            ->assertCanSeeTableRecords(Media::all());
    }

    public function test_it_can_update_media_translations(): void
    {
        $media = $this->createMedia();

        Livewire::test(ListMedia::class)
            ->callAction(
                TestAction::make('edit')->table($media),
                [
                    'title' => ['en' => 'Company Logo', 'fa' => 'لوگوی شرکت'],
                    'alt' => ['en' => 'Logo', 'fa' => 'لوگو'],
                    'caption' => ['en' => 'Our logo', 'fa' => 'لوگوی ما'],
                    'description' => ['en' => 'The company logo', 'fa' => 'توضیحات لوگو'],
                ]
            )
            ->assertNotified();

        $media = $media->fresh();

        $this->assertSame('Company Logo', $media->getCustomProperty('title')['en']);
        $this->assertSame('لوگو', $media->getCustomProperty('alt')['fa']);
        $this->assertSame('Our logo', $media->getCustomProperty('caption')['en']);
        $this->assertSame('توضیحات لوگو', $media->getCustomProperty('description')['fa']);
    }

    private function createMedia(): Media
    {
        $category = CompanyCategory::factory()->create();

        return $category->addMedia(UploadedFile::fake()->image('logo.jpg', 10, 10))
            ->toMediaCollection('logo', 'public');
    }
}
