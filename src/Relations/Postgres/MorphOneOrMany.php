<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
trait MorphOneOrMany
{
    /** @use \Staudenmeir\EloquentJsonRelations\Relations\Postgres\HasOneOrMany<TRelatedModel, TDeclaringModel> */
    use HasOneOrMany {
        getRelationExistenceQuery as getRelationExistenceQueryParent;
    }

    /**
     * Add the constraints for an internal relationship existence query.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param \Illuminate\Database\Eloquent\Builder<TDeclaringModel> $parentQuery
     * @param list<string>|string $columns
     * @return \Illuminate\Database\Eloquent\Builder<TRelatedModel>
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        return $this->getRelationExistenceQueryParent($query, $parentQuery, $columns)
            ->where($this->morphType, $this->morphClass);
    }
}
