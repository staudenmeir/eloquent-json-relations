<?php

namespace Tests\Models;

class Role extends Model
{
    public function users()
    {
        return $this->hasManyJson(User::class, 'options->role_ids');
    }

    public function users2()
    {
        return $this->hasManyJson(User::class, 'options->roles[]->role_id');
    }
}
