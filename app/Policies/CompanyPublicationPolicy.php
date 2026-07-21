<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\CompanyPublication;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CompanyPublicationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CompanyPublication');
    }

    public function view(AuthUser $authUser, CompanyPublication $companyPublication): bool
    {
        return $authUser->can('View:CompanyPublication');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CompanyPublication');
    }

    public function update(AuthUser $authUser, CompanyPublication $companyPublication): bool
    {
        return $authUser->can('Update:CompanyPublication');
    }

    public function delete(AuthUser $authUser, CompanyPublication $companyPublication): bool
    {
        return $authUser->can('Delete:CompanyPublication');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CompanyPublication');
    }

    public function restore(AuthUser $authUser, CompanyPublication $companyPublication): bool
    {
        return $authUser->can('Restore:CompanyPublication');
    }

    public function forceDelete(AuthUser $authUser, CompanyPublication $companyPublication): bool
    {
        return $authUser->can('ForceDelete:CompanyPublication');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CompanyPublication');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CompanyPublication');
    }

    public function replicate(AuthUser $authUser, CompanyPublication $companyPublication): bool
    {
        return $authUser->can('Replicate:CompanyPublication');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CompanyPublication');
    }

    public function approve(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('Approve:Company');
    }

    public function reject(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('Reject:Company');
    }
}
