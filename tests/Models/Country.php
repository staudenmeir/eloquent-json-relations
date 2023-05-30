<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Country extends Model
{
    use HasRelationships;

    public $timestamps = false;

    public function permissions(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->roles(), (new Role())->permissions());
    }

    public function permissions2(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->roles2(), (new Role())->permissions());
    }

    public function roles(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User())->roles());
    }

    public function roles2(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User())->rolesWithObjects());
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
