<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

class UserAsArrayObject extends Model
{
    protected $table = 'users';

    protected $casts = [
        'options' => AsArrayObject::class,
    ];

    public function roles(): BelongsToJson
    {
        return $this->belongsToJson(Role::class, 'options->role_ids');
    }
}
