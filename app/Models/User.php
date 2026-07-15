<?php

namespace App\Models;

use App\Observers\UserObserver;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable(['name', 'family', 'email', 'password', 'phone'])]
#[Hidden(['password', 'remember_token'])]
#[Translatable(['name', 'family'])]
#[ObservedBy(UserObserver::class)]
class User extends Authenticatable implements FilamentUser, HasMedia, HasName
{
    use HasFactory, Notifiable;
    use HasRoles, HasTranslations, InteractsWithMedia;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['super_admin', 'shareholder']);
    }

    /**
     * Filament's admin panel is always Persian (see SetFilamentLocale
     * middleware), regardless of the current app()->getLocale() — which
     * reflects the visitor-facing site's selected language. Without this,
     * Filament's default user-menu name resolution falls back to
     * $user->getAttributeValue('name'), which reads the *current* app
     * locale and would show the wrong translation if it ever diverges
     * from 'fa' (e.g. during Livewire background requests).
     */
    public function getFilamentName(): string
    {
        return trim($this->getTranslation('name', 'fa', false).' '.$this->getTranslation('family', 'fa', false));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Deactivated users keep full dashboard access; this only excludes them
     * from public site output. Must be applied to every query that feeds
     * publicly visible pages (e.g. company listings joined to their owning
     * user, author bylines, public profile pages) once those exist.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereNull('deactivated_at');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->queued();
    }

    public function getTranslatedRoleNameAttribute(): string
    {
        $role = $this->getRoleNames()->first();

        return $role ? __("roles.{$role}") : __('roles.unknown');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Memoized for the lifetime of this model instance so repeated calls
     * within a single request (e.g. from both the profile page and the
     * progress widget) don't recompute the checklist or reload relations.
     */
    protected ?int $profileCompletionPercentage = null;

    public function profileCompletionPercentage(): int
    {
        return $this->profileCompletionPercentage ??= $this->calculateProfileCompletionPercentage();
    }

    /**
     * Weighted checklist for profile completeness. Weights must sum to 100.
     *
     * @return array<string, array{0: int, 1: bool}>
     */
    protected function profileCompletionChecklist(): array
    {
        $profile = $this->profile;
        $locale = app()->getLocale();

        return [
            'name' => [5, filled($this->getTranslation('name', $locale, false))],
            'family' => [5, filled($this->getTranslation('family', $locale, false))],
            'email' => [5, filled($this->email)],
            'phone' => [5, filled($this->phone)],
            'email_verified' => [10, $this->email_verified_at !== null],
            'phone_verified' => [10, $this->phone_verified_at !== null],
            'avatar' => [15, $this->getFirstMedia('avatar') !== null],
            'username' => [5, filled($profile?->username)],
            'national_code' => [5, filled($profile?->national_code)],
            'gender' => [5, $profile?->gender !== null],
            'birth_date' => [5, $profile?->birth_date !== null],
            'country' => [5, $profile?->country_id !== null],
            'city' => [5, filled($profile?->city)],
            'address' => [5, filled($profile?->getTranslation('address', $locale, false))],
            'biography' => [5, filled($profile?->getTranslation('biography', $locale, false))],
            'job_title' => [5, filled($profile?->getTranslation('job_title', $locale, false))],
        ];
    }

    protected function calculateProfileCompletionPercentage(): int
    {
        $earned = array_sum(array_map(
            fn (array $item): int => $item[1] ? $item[0] : 0,
            $this->profileCompletionChecklist(),
        ));

        return (int) round($earned);
    }
}
