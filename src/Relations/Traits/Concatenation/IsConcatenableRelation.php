<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Traits\Concatenation;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
trait IsConcatenableRelation
{
    /**
     * Set the constraints for an eager load of the deep relation.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param list<TDeclaringModel> $models
     * @return void
     */
    public function addEagerConstraintsToDeepRelationship(Builder $query, array $models): void
    {
        $this->addEagerConstraints($models);

        $this->mergeWhereConstraints($query, $this->query);
    }

    /**
     * Merge the where constraints from another query to the current query.
     *
     * @param \Illuminate\Database\Eloquent\Builder<*> $query
     * @param \Illuminate\Database\Eloquent\Builder<*> $from
     * @return \Illuminate\Database\Eloquent\Builder<*>
     */
    public function mergeWhereConstraints(Builder $query, Builder $from): Builder
    {
        $whereBindings = $from->getQuery()->getRawBindings()['where'] ?? [];

        $wheres = $from->getQuery()->wheres;

        $query->withoutGlobalScopes(
            $from->removedScopes()
        )->mergeWheres($wheres, $whereBindings);

        return $query;
    }
}
