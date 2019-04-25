<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Builder;

trait MorphOneOrMany
{
    use HasOneOrMany {
        getRelationExistenceQuery as getRelationExistenceQueryParent;
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Builder $parentQuery
     * @param array|mixed $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        return $this->getRelationExistenceQueryParent($query, $parentQuery, $columns)
            ->where($this->morphType, $this->morphClass);
    }
}
