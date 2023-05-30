<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

class User extends Model
{
    use HasRelationships;

    protected $casts = [
        'options' => 'json',
        'role_ids' => 'json',
        'role_objects' => 'json',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class, 'options->locale_id');
    }

    public function permissions(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->roles(), (new Role())->permissions());
    }

    public function permissions2(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->rolesWithObjects(), (new Role())->permissions());
    }

    public function roles(): BelongsToJson
    {
        return $this->belongsToJson(Role::class, 'options->role_ids');
    }

    public function rolesInColumn(): BelongsToJson
    {
        return $this->belongsToJson(Role::class, 'role_ids');
    }

    public function rolesWithObjects(): BelongsToJson
    {
        return $this->belongsToJson(Role::class, 'options->roles[]->role->id');
    }

    public function rolesWithObjectsInColumn(): BelongsToJson
    {
        return $this->belongsToJson(Role::class, 'role_objects[]->role->id');
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
