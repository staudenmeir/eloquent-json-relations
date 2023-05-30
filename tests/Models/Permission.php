<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

class Permission extends Model
{
    use HasJsonRelationships;
    use HasRelationships;

    public $timestamps = false;

    public function countries(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User())->country());
    }

    public function countries2(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->users2(), (new User())->country());
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function users(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->role(), (new Role())->users());
    }

    public function users2(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->role(), (new Role())->usersWithObjects());
    }
}
