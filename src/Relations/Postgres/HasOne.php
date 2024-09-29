<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Relations\HasOne as Base;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\HasOne<TRelatedModel, TDeclaringModel>
 */
class HasOne extends Base
{
    /** @use \Staudenmeir\EloquentJsonRelations\Relations\Postgres\HasOneOrMany<TRelatedModel, TDeclaringModel> */
    use HasOneOrMany;
}
