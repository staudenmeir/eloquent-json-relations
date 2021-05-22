<?php

namespace Tests\Models;

class User extends Model
{
    protected $casts = [
        'options' => 'json',
    ];

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
        return $this->belongsToJson(Role::class, 'options->roles[]->role->id');
    }

    public function roles3()
    {
        return $this->belongsToJson(Role::class, 'options[]->role_id');
    }

    public function teamMate()
    {
        return $this->hasOneThrough(self::class, Team::class, 'options->owner_id', 'options->team_id');
    }

    public function teamMates()
    {
        return $this->hasManyThrough(self::class, Team::class, 'options->owner_id', 'options->team_id');
    }
}
