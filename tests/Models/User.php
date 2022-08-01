<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

class User extends Model
{
    protected $casts = [
        'options' => 'json',
    ];

    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class, 'options->locale_id');
    }

    public function roles(): BelongsToJson
    {
        return $this->belongsToJson(Role::class, 'options->role_ids');
    }

    public function roles2(): BelongsToJson
    {
        return $this->belongsToJson(Role::class, 'options->roles[]->role->id');
    }

    public function roles3(): BelongsToJson
    {
        return $this->belongsToJson(Role::class, 'options[]->role_id');
    }

    public function teamMate(): HasOneThrough
    {
        return $this->hasOneThrough(self::class, Team::class, 'options->owner_id', 'options->team_id');
    }

    public function teamMates(): HasManyThrough
    {
        return $this->hasManyThrough(self::class, Team::class, 'options->owner_id', 'options->team_id');
    }
}
