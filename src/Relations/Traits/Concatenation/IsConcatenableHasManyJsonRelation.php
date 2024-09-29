<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Traits\Concatenation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
trait IsConcatenableHasManyJsonRelation
{
    /** @use \Staudenmeir\EloquentJsonRelations\Relations\Traits\Concatenation\IsConcatenableRelation<TRelatedModel, TDeclaringModel> */
    use IsConcatenableRelation;

    /**
     * Append the relation's through parents, foreign and local keys to a deep relationship.
     *
     * @param non-empty-list<string> $through
     * @param non-empty-list<array{0: string, 1: string}|callable|string|\Staudenmeir\EloquentHasManyDeep\Eloquent\CompositeKey|null> $foreignKeys
     * @param non-empty-list<array{0: string, 1: string}|callable|string|\Staudenmeir\EloquentHasManyDeep\Eloquent\CompositeKey|null> $localKeys
     * @param int $position
     * @return array{0: non-empty-list<string>,
     *     1: non-empty-list<array{0: string, 1: string}|callable|string|\Staudenmeir\EloquentHasManyDeep\Eloquent\CompositeKey|null>,
     *     2: non-empty-list<array{0: string, 1: string}|callable|string|\Staudenmeir\EloquentHasManyDeep\Eloquent\CompositeKey|null>}
     */
    public function appendToDeepRelationship(array $through, array $foreignKeys, array $localKeys, int $position): array
    {
        if ($position === 0) {
            $foreignKeys[] = function (Builder $query, ?Builder $parentQuery = null) {
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
     * @param list<TDeclaringModel> $models
     * @param \Illuminate\Database\Eloquent\Collection<int, TRelatedModel> $results
     * @param string $relation
     * @param string $type
     * @return list<TDeclaringModel>
     */
    public function matchResultsForDeepRelationship(
        array $models,
        Collection $results,
        string $relation,
        string $type = 'many'
    ): array {
        $dictionary = $this->buildDictionaryForDeepRelationship($results);

        foreach ($models as $model) {
            $key = $this->getDictionaryKey($model->{$this->localKey});

            if (isset($dictionary[$key])) {
                $value = $dictionary[$key];

                $value = $type === 'one'
                    ? (reset($value) ?: null)
                    : $this->related->newCollection($value);

                $model->setRelation($relation, $value);
            }
        }

        return $models;
    }

    /**
     * Build the model dictionary for a deep relation.
     *
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @param \Illuminate\Database\Eloquent\Collection<int, TRelatedModel> $results
     * @return array<int|string, list<TRelatedModel>>
     */
    protected function buildDictionaryForDeepRelationship(Collection $results): array
    {
        $dictionary = [];

        $key = $this->key ? str_replace('->', '.', $this->key) : null;

        foreach ($results as $result) {
            /** @var array<string, mixed> $values */
            $values = json_decode($result->laravel_through_key ?? '[]', true);

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
