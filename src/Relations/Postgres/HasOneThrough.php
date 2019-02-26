<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Relations\HasOneThrough as Base;

class HasOneThrough extends Base
{
    use HasOneOrManyThrough;
}
