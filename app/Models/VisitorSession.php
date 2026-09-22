<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VisitorSession extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'visitor_id',
        'origin_type',
        'origin_source',
        'entry_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'started_at',
        'last_active_at',
        'hits_count',
        'seconds_spent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_active_at' => 'datetime',
        'hits_count' => 'integer',
        'seconds_spent' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = $model->id ?: (string) Str::uuid();
        });
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class, 'session_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(VisitorEvent::class, 'session_id');
    }
}
