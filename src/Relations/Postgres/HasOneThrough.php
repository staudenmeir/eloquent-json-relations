<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Relations\HasOneThrough as Base;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TIntermediateModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\HasOneThrough<TRelatedModel, TIntermediateModel, TDeclaringModel>
 */
class HasOneThrough extends Base
{
    /** @use \Staudenmeir\EloquentJsonRelations\Relations\Postgres\HasOneOrManyThrough<TRelatedModel, TIntermediateModel, TDeclaringModel> */
    use HasOneOrManyThrough;
}
