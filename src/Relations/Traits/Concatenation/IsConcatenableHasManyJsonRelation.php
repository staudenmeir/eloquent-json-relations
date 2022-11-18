<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Traits\Concatenation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;

trait IsConcatenableHasManyJsonRelation
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
            $foreignKeys[] = function (Builder $query, Builder $parentQuery = null) {
                if ($parentQuery) {
                    $this->getRelationExistenceQuery($this->query, $parentQuery);
                }

                $this->mergeWhereConstraints($query, $this->query);
            };

            $localKeys[] = $this->localKey;
        } else {
            $foreignKeys[] = function (Builder $query, JoinClause $join) {
                [$sql, $bindings] = $this->relationExistenceQueryParentKey($query);

                $query->addBinding($bindings, 'join');

                $join->whereJsonContains(
                    $this->getQualifiedPath(),
                    $query->getQuery()->connection->raw($sql)
                );
            };

            $localKeys[] = null;
        }

        return [$through, $foreignKeys, $localKeys];
    }

    /**
     * Get the custom through key for an eager load of the relation.
     *
     * @param string $alias
     * @return \Illuminate\Database\Query\Expression
     */
    public function getThroughKeyForDeepRelationships(string $alias): Expression
    {
        $throughKey = $this->getJsonGrammar($this->query)->compileJsonValueSelect($this->path);

        $alias = $this->query->getQuery()->grammar->wrap($alias);

        return new Expression("$throughKey as $alias");
    }

    /**
     * Match the eagerly loaded results for a deep relationship to their parents.
     *
     * @param array $models
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @param string $relation
     * @return array
     */
    public function matchResultsForDeepRelationship(array $models, Collection $results, string $relation): array
    {
        $dictionary = $this->buildDictionaryForDeepRelationship($results);

        foreach ($models as $model) {
            $key = $this->getDictionaryKey($model->{$this->localKey});

            if (isset($dictionary[$key])) {
                $collection = $this->related->newCollection($dictionary[$key]);

                $model->setRelation($relation, $collection);
            }
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

        $key = $this->key ? str_replace('->', '.', $this->key) : null;

        foreach ($results as $result) {
            $values = json_decode($result->laravel_through_key, true);

            if ($key) {
                $values = array_filter(
                    Arr::pluck($values, $key),
                    fn ($value) => $value !== null
                );
            }

            foreach ($values as $value) {
                $dictionary[$value][] = $result;
            }
        }

        return $dictionary;
    }
}
