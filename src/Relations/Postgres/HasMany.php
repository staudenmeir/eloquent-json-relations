<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Relations\HasMany as Base;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\HasMany<TRelatedModel, TDeclaringModel>
 */
class HasMany extends Base
{
    /** @use \Staudenmeir\EloquentJsonRelations\Relations\Postgres\HasOneOrMany<TRelatedModel, TDeclaringModel> */
    use HasOneOrMany;
}
