<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Relations\MorphOne as Base;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\MorphOne<TRelatedModel, TDeclaringModel>
 */
class MorphOne extends Base
{
    /** @use \Staudenmeir\EloquentJsonRelations\Relations\Postgres\MorphOneOrMany<TRelatedModel, TDeclaringModel> */
    use MorphOneOrMany;
}
