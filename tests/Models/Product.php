<?php

namespace Tests\Models;

class Product extends Model
{
    protected $casts = [
        'options' => 'json',
    ];
}
