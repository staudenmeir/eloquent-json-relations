<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Relations\HasOne as Base;

class HasOne extends Base
{
    use HasOneOrMany;
}
