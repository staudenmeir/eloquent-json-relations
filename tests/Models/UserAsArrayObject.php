<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class UserAsArrayObject extends User
{
    protected $table = 'users';

    protected $casts = [
        'options' => AsArrayObject::class,
    ];
}
