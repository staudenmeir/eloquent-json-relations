<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo as Base;

class BelongsTo extends Base
{
    use IsPostgresRelation;

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
        if ($parentQuery->getQuery()->from == $query->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        $first = $this->jsonColumn($query, $this->related, $this->getQualifiedForeignKeyName(), $this->ownerKey);

        return $query->select($columns)->whereColumn(
            $first,
            '=',
            $query->qualifyColumn($this->ownerKey)
        );
    }

    /**
     * Add the constraints for a relationship query on the same table.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Builder $parentQuery
     * @param array|mixed $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $query->select($columns)->from(
            $query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash()
        );

        $query->getModel()->setTable($hash);

        $first = $this->jsonColumn($query, $this->related, $this->getQualifiedForeignKeyName(), $this->ownerKey);

        return $query->whereColumn(
            $first,
            $hash.'.'.$this->ownerKey
        );
    }
}
