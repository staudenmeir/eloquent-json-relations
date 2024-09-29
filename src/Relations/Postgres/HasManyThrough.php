<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Relations\HasManyThrough as Base;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TIntermediateModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\HasManyThrough<TRelatedModel, TIntermediateModel, TDeclaringModel>
 */
class HasManyThrough extends Base
{
    /** @use \Staudenmeir\EloquentJsonRelations\Relations\Postgres\HasOneOrManyThrough<TRelatedModel, TIntermediateModel, TDeclaringModel> */
    use HasOneOrManyThrough;
}
