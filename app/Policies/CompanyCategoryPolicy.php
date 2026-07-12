<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CompanyCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CompanyCategory');
    }

    public function view(AuthUser $authUser, CompanyCategory $companyCategory): bool
    {
        return $authUser->can('View:CompanyCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CompanyCategory');
    }

    public function update(AuthUser $authUser, CompanyCategory $companyCategory): bool
    {
        return $authUser->can('Update:CompanyCategory');
    }

    public function delete(AuthUser $authUser, CompanyCategory $companyCategory): bool
    {
        return $authUser->can('Delete:CompanyCategory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CompanyCategory');
    }

    public function restore(AuthUser $authUser, CompanyCategory $companyCategory): bool
    {
        return $authUser->can('Restore:CompanyCategory');
    }

    public function forceDelete(AuthUser $authUser, CompanyCategory $companyCategory): bool
    {
        return $authUser->can('ForceDelete:CompanyCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CompanyCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CompanyCategory');
    }

    public function replicate(AuthUser $authUser, CompanyCategory $companyCategory): bool
    {
        return $authUser->can('Replicate:CompanyCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CompanyCategory');
    }

}