<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class UserAsArrayObject extends Model
{
    protected $table = 'users';

    protected $casts = [
        'options' => AsArrayObject::class,
    ];

    public function roles()
    {
        return $this->belongsToJson(Role::class, 'options->role_ids');
    }
}
