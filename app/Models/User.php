<?php

namespace App\Models;

use App\Observers\UserObserver;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable(['name', 'family', 'email', 'password', 'phone'])]
#[Hidden(['password', 'remember_token'])]
#[Translatable(['name', 'family'])]
#[ObservedBy(UserObserver::class)]
class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, Notifiable;
    use HasRoles, HasTranslations;

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
            'password' => 'hashed',
        ];
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
}
