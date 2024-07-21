<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\EloquentJsonRelations\JsonKey;
use Staudenmeir\EloquentJsonRelations\Relations\HasManyJson;
use Staudenmeir\EloquentJsonRelations\Relations\HasOneJson;

class Role extends Model
{
    use HasRelationships;

    public function countries(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User())->country());
    }

    public function countries2(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->usersWithObjects(), (new User())->country());
    }

    public function country(): HasOneDeep
    {
        return $this->hasOneDeepFromRelations($this->users(), (new User())->country());
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    public function projects(): HasManyDeep
    {
        return $this->hasManyThroughJson(Project::class, User::class, new JsonKey('options->role_ids'));
    }

    public function projects2(): HasManyDeep
    {
        return $this->hasManyThroughJson(Project::class, User::class, new JsonKey('options->roles[]->role->id'));
    }

    public function user(): HasOneJson
    {
        return $this->hasOneJson(User::class, 'options->role_ids')
            ->orderByDesc('id');
    }

    public function userWithDefault(): HasOneJson
    {
        return $this->hasOneJson(User::class, 'options->role_ids')
            ->withDefault();
    }

    public function users(): HasManyJson
    {
        return $this->hasManyJson(User::class, 'options->role_ids');
    }

    public function userInColumn(): HasOneJson
    {
        return $this->hasOneJson(User::class, 'role_ids')
            ->orderByDesc('id');
    }

    public function usersInColumn(): HasManyJson
    {
        return $this->hasManyJson(User::class, 'role_ids');
    }

    public function userWithObjects(): HasOneJson
    {
        return $this->hasOneJson(User::class, 'options->roles[]->role->id')
            ->orderByDesc('id');
    }

    public function usersWithObjects(): HasManyJson
    {
        return $this->hasManyJson(User::class, 'options->roles[]->role->id');
    }

    public function userWithObjectsInColumn(): HasOneJson
    {
        return $this->hasOneJson(User::class, 'role_objects[]->role->id')
            ->orderByDesc('id');
    }

    public function usersWithObjectsInColumn(): HasManyJson
    {
        return $this->hasManyJson(User::class, 'role_objects[]->role->id');
    }
}
