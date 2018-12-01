<?php

namespace Tests\Models;

class User extends Model
{
    protected $casts = [
        'options' => 'json'
    ];

    public function comments()
    {
        return $this->morphMany(Comment::class, 'options->commentable');
    }

    public function locale()
    {
        return $this->belongsTo(Locale::class, 'options->locale_id');
    }

    public function roles()
    {
        return $this->belongsToJson(Role::class, 'options->role_ids');
    }

    public function roles2()
    {
        return $this->belongsToJson(Role::class, 'options->roles[]->role_id');
    }

    public function roles3()
    {
        return $this->belongsToJson(Role::class, 'options[]->role_id');
    }
}
