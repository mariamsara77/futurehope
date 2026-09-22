<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Work extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'user_id', 'category_id', 'submitted_name', 'submitted_email',
        'title', 'description', 'status', 'is_published', 'votes_count', 'required_votes',
    ];

    protected $casts = ['is_published' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Work $work) {
            $work->status ??= 'voting';
            $work->required_votes ??= 10;
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(600)->height(400)->sharpen(10);
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkCategory::class, 'category_id');
    }

    public function votes(): HasMany { return $this->hasMany(WorkVote::class); }

    public function hasUserVoted(int $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }

    public function checkAutoApprove(): void
    {
        if ($this->votes_count >= $this->required_votes && in_array($this->status, ['suggested', 'voting'], true)) {
            $this->forceFill(['status' => 'approved', 'is_published' => true])->save();
        }
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('cover', 'thumb') ?: $this->getFirstMediaUrl('cover') ?: null;
    }
}
