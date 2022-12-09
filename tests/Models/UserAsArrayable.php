<?php

namespace Tests\Models;

use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;
use Tests\Casts\ArrayableCast;

class UserAsArrayable extends User
{
    protected $table = 'users';

    protected $casts = [
        'options' => ArrayableCast::class,
    ];

    public function roles(): BelongsToJson
    {
        return $this->belongsToJson(Role::class, 'options->role_ids');
    }

    public function roles3(): BelongsToJson
    {
        return $this->belongsToJson(Role::class, 'options[]->role_id');
    }
}
