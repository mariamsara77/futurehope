<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkCategory extends Model
{
    protected $fillable = ['name', 'slug', 'is_active'];

    public function works(): HasMany
    {
        return $this->hasMany(Work::class, 'category_id');
    }
}