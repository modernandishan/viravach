<?php

use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use App\Support\LocalizedDate;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts::landing')]
class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $status = '';

    public ?int $companyPendingDeletion = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function companies(): LengthAwarePaginator
    {
        $locale = app()->getLocale();
        $search = trim($this->search);

        return Company::query()
            ->where('user_id', auth()->id())
            ->with(['media', 'categories', 'primaryAddress.state', 'planSubscriptions.plan'])
            ->when($search !== '', function ($query) use ($search, $locale) {
                $query->where(function ($query) use ($search, $locale) {
                    $query->where("name->{$locale}", 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($this->status !== '', fn ($query) => $query->where('review_status', $this->status))
            ->latest()
            ->paginate(10);
    }

    /**
     * Same 48-hour, dashboard-only cooldown createCompany() enforces
     * server-side — this only surfaces it here so the button can be
     * disabled before the user fills the whole wizard.
     */
    public function companyCreationCooldownEndsAt(): ?CarbonInterface
    {
        if (auth()->user()->hasAnyRole(['super_admin', 'admin'])) {
            return null;
        }

        return Company::creationCooldownEndsAt(auth()->id());
    }

    /**
     * @return array<int, CompanyReviewStatus>
     */
    public function statusOptions(): array
    {
        return CompanyReviewStatus::cases();
    }

    public function confirmDelete(int $companyId): void
    {
        $this->companyPendingDeletion = $companyId;
    }

    public function delete(): void
    {
        $company = Company::where('user_id', auth()->id())->findOrFail($this->companyPendingDeletion);
        $company->delete();

        $this->companyPendingDeletion = null;

        unset($this->companies);

        session()->flash('company-status', __('companies.deleted_successfully'));
    }

    public function render()
    {
        return $this->view()->title(__('companies.my_companies_title').' | '.__('auth.user-dashboard').' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        <livewire:dashboard-elements.infobar/>

        @if (session('company-status'))
            <div class="alert alert-success">{{ session('company-status') }}</div>
        @endif

        <div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
            <div class="content flex-row-fluid" id="kt_content">
                <div class="card card-flush">
                    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                        <div class="card-title">
                            <div class="d-flex align-items-center position-relative my-1">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <input type="text" wire:model.live.debounce.400ms="search"
                                       class="form-control form-control-solid w-250px ps-12"
                                       placeholder="{{ __('companies.search_placeholder') }}">
                            </div>
                        </div>
                        <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                            <div class="w-100 mw-150px">
                                <select wire:model.live="status" class="form-select form-select-solid">
                                    <option value="">{{ __('companies.filter_all_statuses') }}</option>
                                    @foreach ($this->statusOptions() as $option)
                                        <option value="{{ $option->value }}">{{ __('companies.status_'.$option->value) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @php
                                $cooldownEndsAt = $this->companyCreationCooldownEndsAt();
                            @endphp
                            @if ($cooldownEndsAt !== null)
                                <button type="button" class="btn btn-primary text-nowrap" disabled
                                        data-bs-toggle="tooltip"
                                        title="{{ __('companies.company_creation_cooldown', ['time' => LocalizedDate::format($cooldownEndsAt, LocalizedDate::FORMAT_DATETIME)]) }}">
                                    {{ __('menu.create_new_company') }}
                                </button>
                            @else
                                <a href="{{ route('create.company') }}" class="btn btn-primary text-nowrap">
                                    {{ __('menu.create_new_company') }}
                                </a>
                            @endif
                        </div>
                    </div>
                    @if ($cooldownEndsAt !== null)
                        <div class="px-9">
                            <div class="alert alert-warning">
                                {{ __('companies.company_creation_cooldown', ['time' => LocalizedDate::format($cooldownEndsAt, LocalizedDate::FORMAT_DATETIME)]) }}
                            </div>
                        </div>
                    @endif
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-200px">{{ __('companies.table_name') }}</th>
                                    <th class="min-w-125px">{{ __('companies.table_category') }}</th>
                                    <th class="min-w-100px">{{ __('companies.table_state') }}</th>
                                    <th class="min-w-125px">{{ __('companies.table_plan') }}</th>
                                    <th class="min-w-100px">{{ __('companies.table_status') }}</th>
                                    <th class="text-end min-w-100px">{{ __('companies.table_actions') }}</th>
                                </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-600">
                                @forelse ($this->companies as $company)
                                    <tr wire:key="company-{{ $company->id }}">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-50px bg-light me-5">
                                                    @if ($company->hasMedia('logo'))
                                                        <img src="{{ $company->getFirstMediaUrl('logo', 'webp') }}" alt="{{ $company->name }}" class="p-2">
                                                    @else
                                                        <span class="symbol-label bg-light-primary text-primary fw-bold fs-3">
                                                            {{ \Illuminate\Support\Str::substr($company->name, 0, 1) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <a href="{{ route('edit.company', $company) }}" class="text-gray-800 text-hover-primary fs-5 fw-bold">
                                                    {{ $company->name }}
                                                </a>
                                            </div>
                                        </td>
                                        <td>{{ $company->categories->map(fn ($category) => $category->title)->join('، ') }}</td>
                                        <td>{{ $company->primaryAddress?->state?->name }}</td>
                                        <td>
                                            @if ($subscription = $company->activeSubscription())
                                                <div class="fw-bold">{{ $subscription->plan->name }}</div>
                                                <div class="text-muted fs-8">
                                                    {{ $subscription->ends_at ? __('companies.plan_expires_at', ['date' => LocalizedDate::format($subscription->ends_at)]) : __('companies.plan_never_expires') }}
                                                </div>
                                            @else
                                                <span class="text-muted">{{ __('companies.no_plan') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $company->review_status->getColor() }}">
                                                {{ __('companies.status_'.$company->review_status->value) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('subscriptions', ['company' => $company->id]) }}"
                                               class="btn btn-sm btn-light btn-active-light-primary me-2">
                                                {{ __('companies.action_assign_subscription') }}
                                            </a>
                                            <a href="{{ route('edit.company', $company) }}"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-2"
                                               data-bs-toggle="tooltip" title="{{ __('companies.action_edit') }}">
                                                <i class="ki-duotone ki-pencil fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </a>
                                            <button type="button" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#kt_modal_delete_company"
                                                    wire:click="confirmDelete({{ $company->id }})"
                                                    title="{{ __('companies.action_delete') }}">
                                                <i class="ki-duotone ki-trash fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                    <span class="path4"></span>
                                                    <span class="path5"></span>
                                                </i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-10">
                                            {{ __('companies.no_companies_found') }}
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-5">
                            {{ $this->companies->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--begin::Modal - delete company confirmation-->
        <div class="modal fade" id="kt_modal_delete_company" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-450px">
                <div class="modal-content modal-rounded">
                    <div class="modal-header py-7 d-flex justify-content-between">
                        <h2>{{ __('companies.delete_confirm_title') }}</h2>
                        <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                    </div>
                    <div class="modal-body">
                        <p class="text-gray-600">{{ __('companies.delete_confirm_body') }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            {{ __('companies.delete_cancel_button') }}
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" wire:click="delete">
                            {{ __('companies.delete_confirm_button') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Modal - delete company confirmation-->
    </div>
</div>
