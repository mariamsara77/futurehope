<?php

namespace App\Models;

use App\Concerns\HasTeams;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Contracts\Auth\CanResetPassword;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'password', 'google_id', 'avatar', 'status', 'current_team_id'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasMedia, CanResetPassword
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, InteractsWithMedia, TwoFactorAuthenticatable;

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    use HasTeams {
        HasTeams::teams as teamsRelation;
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
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10);
    }

    // Avatar helper (Google বা Media Library)
    public function getAvatarUrlAttribute(): string
    {
        if ($this->hasMedia('avatar')) {
            return $this->getFirstMediaUrl('avatar', 'thumb');
        }

        return $this->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($this->name);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->teamsRelation();
    }

    public function works()
    {
        return $this->hasMany(Work::class);
    }

    public function votes()
    {
        return $this->hasMany(WorkVote::class);
    }

    public function assignedWorks()
    {
        return $this->hasMany(Work::class, 'assigned_to');
    }

    /**
 * Get the user's initials.
 */
public function initials(): string
{
    return Str::of($this->name)
        ->explode(' ')
        ->map(fn (string $name) => Str::of($name)->substr(0, 1)->upper())
        ->take(2)
        ->implode('');
}
}