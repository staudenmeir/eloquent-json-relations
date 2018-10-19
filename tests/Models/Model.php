<?php

namespace Tests\Models;

use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    use HasJsonRelationships;

    public $timestamps = false;
}
