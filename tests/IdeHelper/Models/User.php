<?php

namespace Tests\IdeHelper\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

class User extends Model
{
    use HasJsonRelationships;

    public function roles(): BelongsToJson
    {
        return $this->belongsToJson(Role::class, 'role_ids');
    }
}
