<?php

namespace Tests\Models;

use Staudenmeir\EloquentJsonRelations\Relations\HasManyJson;

class Role extends Model
{
    public function users(): HasManyJson
    {
        return $this->hasManyJson(User::class, 'options->role_ids');
    }

    public function users2(): HasManyJson
    {
        return $this->hasManyJson(User::class, 'options->roles[]->role->id');
    }
}
