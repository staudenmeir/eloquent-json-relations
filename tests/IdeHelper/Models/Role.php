<?php

namespace Tests\IdeHelper\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\HasManyJson;

class Role extends Model
{
    use HasJsonRelationships;

    public function users(): HasManyJson
    {
        return $this->hasManyJson(User::class, 'role_ids');
    }
}
