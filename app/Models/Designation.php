<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'order', 'is_committee', 'is_active'])]
class Designation extends Model
{
    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }

    public function users()
    {
        return $this->hasManyThrough(User::class, Profile::class);
    }
}