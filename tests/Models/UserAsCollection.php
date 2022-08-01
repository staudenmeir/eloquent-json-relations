<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

class UserAsCollection extends Model
{
    protected $table = 'users';

    protected $casts = [
        'options' => AsCollection::class,
    ];

    public function roles(): BelongsToJson
    {
        return $this->belongsToJson(Role::class, 'options->role_ids');
    }
}
