<?php

namespace Tests\Models;

class Post extends Model
{
    protected $casts = [
        'options' => 'json'
    ];

    public function recommendations()
    {
        return $this->belongsToJson(self::class, 'options->recommendations');
    }

    public function recommenders()
    {
        return $this->hasManyJson(self::class, 'options->recommendations');
    }
}
