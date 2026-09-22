<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'visitor_id',
        'title',
        'url',
        'url_hash',
        'route_name',
        'load_time_ms',
        'view_duration',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'load_time_ms' => 'integer',
        'view_duration' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(VisitorSession::class, 'session_id');
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }
}