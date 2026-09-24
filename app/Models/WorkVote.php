<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkVote extends Model
{
    protected $fillable = ['work_id', 'user_id'];

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($vote) {
            $work = $vote->work;
            $work->increment('votes_count');
            $work->refresh();
            $work->checkAutoApprove();
        });

        static::deleted(function ($vote) {
            $work = $vote->work;
            $work->decrement('votes_count');
        });
    }
}