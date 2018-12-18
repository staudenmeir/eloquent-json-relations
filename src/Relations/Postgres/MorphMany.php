<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Relations\MorphMany as Base;

class MorphMany extends Base
{
    use MorphOneOrMany;
}
