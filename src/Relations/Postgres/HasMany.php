<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Relations\HasMany as Base;

class HasMany extends Base
{
    use HasOneOrMany;
}
