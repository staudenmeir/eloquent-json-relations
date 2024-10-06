<?php

namespace Staudenmeir\EloquentJsonRelations\Types\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

class User extends Model
{
    use HasJsonRelationships;
}
