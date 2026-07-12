<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\CompanyCategories\Pages\CreateCompanyCategory;
use App\Filament\Resources\CompanyCategories\Pages\EditCompanyCategory;
use App\Filament\Resources\CompanyCategories\Pages\ListCompanyCategories;
use App\Models\CompanyCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CompanyCategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $user->givePermissionTo(
            collect(['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny'])
                ->map(fn (string $ability) => Permission::firstOrCreate([
                    'name' => "{$ability}:CompanyCategory",
                    'guard_name' => 'web',
                ]))
        );

        $this->actingAs($user);
    }

    public function test_it_can_list_company_categories(): void
    {
        $categories = CompanyCategory::factory()->count(3)->create();

        Livewire::test(ListCompanyCategories::class)
            ->assertCanSeeTableRecords($categories);
    }

    public function test_it_can_create_a_company_category_with_translations(): void
    {
        Livewire::test(CreateCompanyCategory::class)
            ->fillForm([
                'slug' => 'construction',
                'sort_order' => 1,
                'is_active' => true,
                'title' => [
                    'en' => 'Construction',
                    'fa' => 'ساخت‌وساز',
                ],
                'description' => [
                    'en' => 'Construction companies',
                    'fa' => 'شرکت‌های ساخت‌وساز',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = CompanyCategory::sole();

        $this->assertSame('construction', $category->slug);
        $this->assertSame('Construction', $category->getTranslation('title', 'en'));
        $this->assertSame('ساخت‌وساز', $category->getTranslation('title', 'fa'));
    }

    public function test_it_can_update_a_company_category(): void
    {
        $category = CompanyCategory::factory()->create(['slug' => 'old-slug']);

        Livewire::test(EditCompanyCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'slug' => 'new-slug',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('new-slug', $category->fresh()->slug);
    }
}
