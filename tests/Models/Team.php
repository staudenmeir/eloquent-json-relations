<?php

namespace Tests\Models;

class Team extends Model
{
    protected $casts = [
        'options' => 'json',
    ];
}
