<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;

class UserAsCollection extends Model
{
    protected $table = 'users';

    protected $casts = [
        'options' => AsCollection::class,
    ];

    public function roles()
    {
        return $this->belongsToJson(Role::class, 'options->role_ids');
    }
}
