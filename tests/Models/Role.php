<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\EloquentJsonRelations\JsonKey;
use Staudenmeir\EloquentJsonRelations\Relations\HasManyJson;

class Role extends Model
{
    use HasRelationships;

    public function countries(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User())->country());
    }

    public function countries2(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->users2(), (new User())->country());
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

    public function users(): HasManyJson
    {
        return $this->hasManyJson(User::class, 'options->role_ids');
    }

    public function users2(): HasManyJson
    {
        return $this->hasManyJson(User::class, 'options->roles[]->role->id');
    }

    public function users3(): HasManyJson // TODO
    {
        return $this->hasManyJson(User::class, 'options->nested[*]->role_ids');
    }

    public function users4(): HasManyJson // TODO
    {
        return $this->hasManyJson(User::class, 'options->nested[*]->roles[]->role->id');
    }
}
