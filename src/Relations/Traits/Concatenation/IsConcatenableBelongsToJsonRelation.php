<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Traits\Concatenation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;

trait IsConcatenableBelongsToJsonRelation
{
    use IsConcatenableRelation;

    /**
     * Append the relation's through parents, foreign and local keys to a deep relationship.
     *
     * @param \Illuminate\Database\Eloquent\Model[] $through
     * @param array $foreignKeys
     * @param array $localKeys
     * @param int $position
     * @return array
     */
    public function appendToDeepRelationship(array $through, array $foreignKeys, array $localKeys, int $position): array
    {
        if ($position === 0) {
            $foreignKeys[] = $this->ownerKey;

            $localKeys[] = function (Builder $query, Builder $parentQuery = null) {
                if ($parentQuery) {
                    $this->getRelationExistenceQuery($this->query, $parentQuery);
                }

                $this->mergeWhereConstraints($query, $this->query);
            };
        } else {
            $foreignKeys[] = function (Builder $query, JoinClause $join) {
                $ownerKey = $this->query->qualifyColumn($this->ownerKey);

                [$sql, $bindings] = $this->relationExistenceQueryOwnerKey($query, $ownerKey);

                $query->addBinding($bindings, 'join');

                $this->whereJsonContainsOrMemberOf(
                    $join,
                    $this->getQualifiedPath(),
                    $query->getQuery()->connection->raw($sql)
                );
            };

            $localKeys[] = null;
        }

        return [$through, $foreignKeys, $localKeys];
    }

    /**
     * Match the eagerly loaded results for a deep relationship to their parents.
     *
     * @param array $models
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @param string $relation
     * @param string $type
     * @return array
     */
    public function matchResultsForDeepRelationship(
        array $models,
        Collection $results,
        string $relation,
        string $type = 'many'
    ): array {
        $dictionary = $this->buildDictionaryForDeepRelationship($results);

        foreach ($models as $model) {
            $matches = [];

            foreach ($this->getForeignKeys($model) as $id) {
                if (isset($dictionary[$id])) {
                    $matches = array_merge($matches, $dictionary[$id]);
                }
            }

            $value = $type === 'one'
                ? (reset($matches) ?: null)
                : $this->related->newCollection($matches);

            $model->setRelation($relation, $value);
        }

        return $models;
    }

    /**
     * Build the model dictionary for a deep relation.
     *
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @return array
     */
    protected function buildDictionaryForDeepRelationship(Collection $results): array
    {
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->laravel_through_key][] = $result;
        }

        return $dictionary;
    }
}
