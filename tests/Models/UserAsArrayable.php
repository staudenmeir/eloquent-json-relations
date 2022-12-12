<?php

namespace Tests\Models;

use Tests\Casts\ArrayableCast;

class UserAsArrayable extends User
{
    protected $table = 'users';

    protected $casts = [
        'options' => ArrayableCast::class,
    ];
}
