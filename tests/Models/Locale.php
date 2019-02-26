<?php

namespace Tests\Models;

class Locale extends Model
{
    public function post()
    {
        return $this->hasOneThrough(Post::class, User::class, 'options->locale_id', 'options->user_id');
    }

    public function posts()
    {
        return $this->hasManyThrough(Post::class, User::class, 'options->locale_id', 'options->user_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'options->locale_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'options->locale_id');
    }
}
