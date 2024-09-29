<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
trait HasOneOrMany
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
        if ($query->getQuery()->from == $parentQuery->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        $second = $this->jsonColumn($query, $this->parent, $this->getExistenceCompareKey(), $this->localKey);

        $query->select($columns)->whereColumn(
            $this->getQualifiedParentKeyName(),
            '=',
            $second // @phpstan-ignore-line
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

        $query->getModel()->setTable($hash);

        $second = $this->jsonColumn($query, $this->parent, $hash.'.'.$this->getForeignKeyName(), $this->localKey);

        $query->select($columns)->whereColumn(
            $this->getQualifiedParentKeyName(),
            '=',
            $second // @phpstan-ignore-line
        );

        return $query;
    }
}
