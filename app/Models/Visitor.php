<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Visitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hash',
        'ip_address',
        'browser_family',
        'os_family',
        'device_type',
        'device_model',
        'country_code',
        'city_name',
        'timezone',
        'is_pwa',
        'has_installed_pwa',
        'app_version',
        'is_bot',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'is_pwa' => 'boolean',
        'has_installed_pwa' => 'boolean',
        'is_bot' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function isOnline(): bool
    {
        return $this->last_seen_at?->gt(now()->subMinutes(5)) ?? false;
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(VisitorSession::class)->latest('started_at');
    }

    public function lastSession(): HasOne
    {
        return $this->hasOne(VisitorSession::class)->latestOfMany('started_at');
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class);
    }

    public function events(): HasManyThrough
    {
        return $this->hasManyThrough(
            VisitorEvent::class,
            VisitorSession::class,
            'visitor_id',
            'session_id',
            'id',
            'id'
        );
    }

    public function eventsThroughSessions(): HasManyThrough
    {
        return $this->hasManyThrough(
            VisitorEvent::class,
            VisitorSession::class,
            'visitor_id',
            'session_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRealUsers(Builder $query): Builder
    {
        return $query->where('is_bot', false);
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('last_seen_at', '>=', now()->subMinutes(5));
    }

    protected function locationFriendly(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->city_name && $this->country_code)
                ? "{$this->city_name}, {$this->country_code}"
                : ($this->country_code ?? 'Unknown')
        );
    }

    protected function browserIcon(): Attribute
    {
        return Attribute::make(
            get: function () {
                $browser = strtolower($this->browser_family ?? '');

                return match (true) {
                    str_contains($browser, 'chrome') => 'browser-chrome',
                    str_contains($browser, 'firefox') => 'browser-firefox',
                    str_contains($browser, 'safari') => 'browser-safari',
                    str_contains($browser, 'edge') => 'browser-edge',
                    default => 'globe-alt',
                };
            }
        );
    }
}
