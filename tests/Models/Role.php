<?php

namespace Tests\Models;

class Role extends Model
{
    public function users()
    {
        return $this->hasManyJson(User::class, 'options->role_ids');
    }
}
