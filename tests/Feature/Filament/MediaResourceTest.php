<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\CompanyCategory;
use App\Models\MediaLibraryEntry;
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

    public function test_user_avatars_appear_in_the_media_list(): void
    {
        $this->createMedia();

        $avatar = User::factory()->create()
            ->addMedia(UploadedFile::fake()->image('avatar.jpg', 10, 10))
            ->toMediaCollection('avatar', 'public');

        Livewire::test(ListMedia::class)
            ->assertCanSeeTableRecords(Media::all())
            ->assertCanSeeTableRecords([$avatar]);
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

    public function test_a_library_uploaded_media_row_can_be_deleted_from_the_list(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $file = UploadedFile::fake()->image('banner.jpg', 10, 10);
        Storage::disk('local')->put('uploads/banner.jpg', $file->getContent());

        Livewire::test(ListMedia::class)
            ->callAction(TestAction::make('upload'), ['image_files' => ['uploads/banner.jpg']])
            ->assertNotified();

        $media = Media::query()->where('collection_name', 'library_images')->firstOrFail();

        $pathOnDisk = $media->getPathRelativeToRoot();
        $mediaDisk = $media->disk;

        Livewire::test(ListMedia::class)
            ->callAction(TestAction::make('delete')->table($media))
            ->assertSuccessful();

        $this->assertModelMissing($media);
        // spatie's Media::delete() removes the underlying file as well.
        $this->assertTrue(Storage::disk($mediaDisk)->fileMissing($pathOnDisk));
    }

    public function test_media_owned_by_a_real_model_cannot_be_deleted_from_the_list(): void
    {
        $media = $this->createMedia();

        Livewire::test(ListMedia::class)
            ->assertActionHidden(TestAction::make('delete')->table($media));

        $this->assertModelExists($media);
    }

    private function createMedia(): Media
    {
        $category = CompanyCategory::factory()->create();

        return $category->addMedia(UploadedFile::fake()->image('logo.jpg', 10, 10))
            ->toMediaCollection('logo', 'public');
    }

    public function test_it_can_upload_a_library_image_from_the_header_action(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->image('banner.jpg', 10, 10);
        Storage::disk('local')->put('uploads/banner.jpg', $file->getContent());

        Livewire::test(ListMedia::class)
            ->callAction(
                TestAction::make('upload'),
                [
                    'image_files' => ['uploads/banner.jpg'],
                    'title' => ['en' => 'Banner', 'fa' => 'بنر'],
                    'alt' => ['en' => 'A banner', 'fa' => 'یک بنر'],
                    'caption' => ['en' => 'Header banner', 'fa' => 'بنر هدر'],
                    'description' => ['en' => 'The header banner', 'fa' => 'بنر بالای صفحه'],
                ]
            )
            ->assertNotified();

        $media = Media::query()->where('collection_name', 'library_images')->first();

        $this->assertNotNull($media);
        // Every manual upload attaches to the single shared MediaLibraryEntry
        // row — that is the "no owner yet" owner.
        $this->assertSame(MediaLibraryEntry::class, $media->model_type);
        $this->assertSame(MediaLibraryEntry::shared()->id, $media->model_id);
        $this->assertSame('Banner', $media->getCustomProperty('title')['en']);
        $this->assertSame('یک بنر', $media->getCustomProperty('alt')['fa']);
    }

    public function test_repeated_uploads_reuse_the_same_library_entry_owner(): void
    {
        Storage::fake('local');

        foreach (['one.jpg', 'two.jpg'] as $name) {
            $file = UploadedFile::fake()->image($name, 10, 10);
            Storage::disk('local')->put("uploads/{$name}", $file->getContent());

            Livewire::test(ListMedia::class)
                ->callAction(TestAction::make('upload'), ['image_files' => ["uploads/{$name}"]])
                ->assertNotified();
        }

        $this->assertSame(1, MediaLibraryEntry::query()->count());
        $this->assertSame(
            2,
            Media::query()->where('model_type', MediaLibraryEntry::class)->count()
        );
    }

    public function test_view_and_copy_link_actions_are_available_on_any_media_row(): void
    {
        // A row owned by a real model (category logo) and a manually
        // uploaded library row: the actions must exist on both.
        $owned = $this->createMedia();

        $file = UploadedFile::fake()->image('banner.jpg', 10, 10);
        Storage::disk('local')->put('uploads/banner.jpg', $file->getContent());

        Livewire::test(ListMedia::class)
            ->callAction(TestAction::make('upload'), ['image_files' => ['uploads/banner.jpg']])
            ->assertNotified();

        $library = Media::query()->where('collection_name', 'library_images')->firstOrFail();

        foreach ([$owned, $library] as $media) {
            Livewire::test(ListMedia::class)
                ->assertActionVisible(TestAction::make('view')->table($media))
                ->assertActionVisible(TestAction::make('copy_link')->table($media));
        }
    }

    public function test_the_view_action_targets_the_media_public_url_in_a_new_tab(): void
    {
        $media = $this->createMedia();

        // The view action renders as a plain anchor (a URL action), so its
        // href carries the resolved public URL and opens a new tab; the
        // copy action is the fallback Alpine anchor with a click handler.
        Livewire::test(ListMedia::class)
            ->assertSee('target="_blank"', escape: false)
            ->assertSee($media->getUrl(), escape: false)
            ->assertSee('x-on:click.prevent', escape: false);
    }
}
