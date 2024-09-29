<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TIntermediateModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
trait HasOneOrManyThrough
{
    use IsPostgresRelation;

    /**
     * Set the join clause on the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel>|null $query
     * @return void
     */
    protected function performJoin(?Builder $query = null)
    {
        $query = $query ?: $this->query;

        $farKey = $this->jsonColumn($query, $this->throughParent, $this->getQualifiedFarKeyName(), $this->secondLocalKey);

        $query->join($this->throughParent->getTable(), $this->getQualifiedParentKeyName(), '=', $farKey);

        if ($this->throughParentSoftDeletes()
            && method_exists($this->throughParent, 'getQualifiedDeletedAtColumn')) {
            $query->whereNull($this->throughParent->getQualifiedDeletedAtColumn());
        }
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
        if ($parentQuery->getQuery()->from === $query->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        if ($parentQuery->getQuery()->from === $this->throughParent->getTable()) {
            return $this->getRelationExistenceQueryForThroughSelfRelation($query, $parentQuery, $columns);
        }

        $this->performJoin($query);

        $firstKey = $this->jsonColumn($query, $this->farParent, $this->getQualifiedFirstKeyName(), $this->localKey);

        $query->select($columns)->whereColumn(
            $this->getQualifiedLocalKeyName(),
            '=',
            $firstKey // @phpstan-ignore-line
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
        $query->from($query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash());

        $farKey = $this->jsonColumn($query, $this->throughParent, $hash.'.'.$this->secondKey, $this->secondLocalKey);

        $query->join($this->throughParent->getTable(), $this->getQualifiedParentKeyName(), '=', $farKey);

        $query->getModel()->setTable($hash);

        /** @var string $parentFrom */
        $parentFrom = $parentQuery->getQuery()->from;

        $firstKey = $this->jsonColumn($query, $this->farParent, $this->getQualifiedFirstKeyName(), $this->localKey);

        $query->select($columns)->whereColumn(
            "$parentFrom.$this->localKey",
            '=',
            $firstKey // @phpstan-ignore-line
        );

        return $query;
    }

    /**
     * Add the constraints for a relationship query on the same table as the through parent.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param \Illuminate\Database\Eloquent\Builder<TDeclaringModel> $parentQuery
     * @param list<string>|string $columns
     * @return \Illuminate\Database\Eloquent\Builder<TRelatedModel>
     */
    public function getRelationExistenceQueryForThroughSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $table = $this->throughParent->getTable().' as '.$hash = $this->getRelationCountHash();

        $farKey = $this->jsonColumn($query, $this->throughParent, $this->getQualifiedFarKeyName(), $this->secondLocalKey);

        $query->join($table, $hash.'.'.$this->secondLocalKey, '=', $farKey);

        if ($this->throughParentSoftDeletes()
            && method_exists($this->throughParent, 'getDeletedAtColumn')) {
            $query->whereNull($hash.'.'.$this->throughParent->getDeletedAtColumn());
        }

        /** @var string $parentFrom */
        $parentFrom = $parentQuery->getQuery()->from;

        $firstKey = $this->jsonColumn($query, $this->farParent, $hash.'.'.$this->firstKey, $this->localKey);

        $query->select($columns)->whereColumn(
            "$parentFrom.$this->localKey",
            '=',
            $firstKey // @phpstan-ignore-line
        );

        return $query;
    }
}
