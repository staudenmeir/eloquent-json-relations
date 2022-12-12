<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;

class UserAsCollection extends User
{
    protected $table = 'users';

    protected $casts = [
        'options' => AsCollection::class,
    ];
}
