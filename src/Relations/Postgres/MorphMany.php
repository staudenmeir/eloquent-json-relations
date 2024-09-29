<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Relations\MorphMany as Base;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\MorphMany<TRelatedModel, TDeclaringModel>
 */
class MorphMany extends Base
{
    /** @use \Staudenmeir\EloquentJsonRelations\Relations\Postgres\MorphOneOrMany<TRelatedModel, TDeclaringModel> */
    use MorphOneOrMany;
}
