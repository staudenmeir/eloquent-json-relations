<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Relations\MorphOne as Base;

class MorphOne extends Base
{
    use MorphOneOrMany;
}
