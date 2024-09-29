<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo as Base;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\BelongsTo<TRelatedModel, TDeclaringModel>
 */
class BelongsTo extends Base
{
    use IsPostgresRelation;

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
        if ($parentQuery->getQuery()->from == $query->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        $first = $this->jsonColumn($query, $this->related, $this->getQualifiedForeignKeyName(), $this->ownerKey);

        $query->select($columns)->whereColumn(
            $first,
            '=',
            $query->qualifyColumn($this->ownerKey)
        );

        return $query;
    }

    /**
     * Add the constraints for a relationship query on the same table.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param \Illuminate\Database\Eloquent\Builder<TDeclaringModel> $parentQuery
     * @param list<string>|string $columns
     * @return \Illuminate\Database\Eloquent\Builder<TRelatedModel>
     */
    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $query->select($columns)->from(
            $query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash()
        );

        $query->getModel()->setTable($hash);

        $first = $this->jsonColumn($query, $this->related, $this->getQualifiedForeignKeyName(), $this->ownerKey);

        $query->whereColumn(
            $first,
            $hash.'.'.$this->ownerKey
        );

        return $query;
    }
}
