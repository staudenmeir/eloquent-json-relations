<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Locale extends Model
{
    public function post(): HasOneThrough
    {
        return $this->hasOneThrough(Post::class, User::class, 'options->locale_id', 'options->user_id');
    }

    public function posts(): HasManyThrough
    {
        return $this->hasManyThrough(Post::class, User::class, 'options->locale_id', 'options->user_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'options->locale_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'options->locale_id');
    }
}
