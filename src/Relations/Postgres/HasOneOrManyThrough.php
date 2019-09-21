<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Builder;

trait HasOneOrManyThrough
{
    use IsPostgresRelation;

    /**
     * Set the join clause on the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder|null $query
     * @return void
     */
    protected function performJoin(Builder $query = null)
    {
        $query = $query ?: $this->query;

        $farKey = $this->jsonColumn($query, $this->throughParent, $this->getQualifiedFarKeyName(), $this->secondLocalKey);

        $query->join($this->throughParent->getTable(), $this->getQualifiedParentKeyName(), '=', $farKey);

        if ($this->throughParentSoftDeletes()) {
            $query->whereNull($this->throughParent->getQualifiedDeletedAtColumn());
        }
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
        if ($parentQuery->getQuery()->from === $query->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        if ($parentQuery->getQuery()->from === $this->throughParent->getTable()) {
            return $this->getRelationExistenceQueryForThroughSelfRelation($query, $parentQuery, $columns);
        }

        $this->performJoin($query);

        $firstKey = $this->jsonColumn($query, $this->farParent, $this->getQualifiedFirstKeyName(), $this->localKey);

        return $query->select($columns)->whereColumn(
            $this->getQualifiedLocalKeyName(),
            '=',
            $firstKey
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
        $query->from($query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash());

        $farKey = $this->jsonColumn($query, $this->throughParent, $hash.'.'.$this->secondKey, $this->secondLocalKey);

        $query->join($this->throughParent->getTable(), $this->getQualifiedParentKeyName(), '=', $farKey);

        $query->getModel()->setTable($hash);

        $firstKey = $this->jsonColumn($query, $this->farParent, $this->getQualifiedFirstKeyName(), $this->localKey);

        return $query->select($columns)->whereColumn(
            $parentQuery->getQuery()->from.'.'.$this->localKey,
            '=',
            $firstKey
        );
    }

    /**
     * Add the constraints for a relationship query on the same table as the through parent.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Builder $parentQuery
     * @param array|mixed $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQueryForThroughSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $table = $this->throughParent->getTable().' as '.$hash = $this->getRelationCountHash();

        $farKey = $this->jsonColumn($query, $this->throughParent, $this->getQualifiedFarKeyName(), $this->secondLocalKey);

        $query->join($table, $hash.'.'.$this->secondLocalKey, '=', $farKey);

        if ($this->throughParentSoftDeletes()) {
            $query->whereNull($hash.'.'.$this->throughParent->getDeletedAtColumn());
        }

        $firstKey = $this->jsonColumn($query, $this->farParent, $hash.'.'.$this->firstKey, $this->localKey);

        return $query->select($columns)->whereColumn(
            $parentQuery->getQuery()->from.'.'.$this->localKey,
            '=',
            $firstKey
        );
    }
}
