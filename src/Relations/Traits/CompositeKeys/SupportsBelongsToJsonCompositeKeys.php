<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Traits\CompositeKeys;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
trait SupportsBelongsToJsonCompositeKeys
{
    /**
     * Determine whether the relationship has a composite key.
     *
     * @return bool
     */
    protected function hasCompositeKey(): bool
    {
        /** @var list<string>|string $foreignKey */
        $foreignKey = $this->foreignKey;

        return is_array($foreignKey);
    }

    /**
     * Set the base constraints on the relation query for a composite key.
     *
     * @return void
     */
    protected function addConstraintsWithCompositeKey(): void
    {
        /** @var list<string> $ownerKey */
        $ownerKey = $this->ownerKey;

        $columns = array_slice($ownerKey, 1);

        foreach ($columns as $column) {
            $this->query->where(
                $this->related->qualifyColumn($column),
                '=',
                $this->child->$column
            );
        }
    }

    /**
     * Set the constraints for an eager load of the relation for a composite key.
     *
     * @param list<TDeclaringModel> $models
     * @return void
     */
    protected function addEagerConstraintsWithCompositeKey(array $models): void
    {
        /** @var list<string> $foreignKey */
        $foreignKey = $this->foreignKey;

        /** @var list<string> $ownerKey */
        $ownerKey = $this->ownerKey;

        $keys = (new BaseCollection($models))->map(
            function (Model $model) use ($foreignKey) {
                return array_map(
                    fn (string $column) => $model[$column],
                    $foreignKey
                );
            }
        )->values()->unique(null, true)->all();

        $this->query->where(
            function (Builder $query) use ($keys, $ownerKey) {
                foreach ($keys as $key) {
                    $query->orWhere(
                        function (Builder $query) use ($key, $ownerKey) {
                            foreach ($ownerKey as $i => $column) {
                                if ($i === 0) {
                                    $query->whereIn(
                                        $this->related->qualifyColumn($column),
                                        $key[$i]
                                    );
                                } else {
                                    $query->where(
                                        $this->related->qualifyColumn($column),
                                        '=',
                                        $key[$i]
                                    );
                                }
                            }
                        }
                    );
                }
            }
        );
    }

    /**
     * Match the eagerly loaded results to their parents for a composite key.
     *
     * @param list<TDeclaringModel> $models
     * @param \Illuminate\Database\Eloquent\Collection<int, TRelatedModel> $results
     * @param string $relation
     * @return list<TDeclaringModel>
     */
    protected function matchWithCompositeKey(array $models, Collection $results, string $relation): array
    {
        /** @var list<string> $ownerKey */
        $ownerKey = $this->ownerKey;

        $dictionary = $this->buildDictionaryWithCompositeKey($results);

        foreach ($models as $model) {
            $matches = [];

            $additionalValues = array_map(
                fn (string $key) => $model->$key,
                array_slice($ownerKey, 1)
            );

            foreach ($this->getForeignKeys($model) as $id) {
                $values = $additionalValues;

                array_unshift($values, $id);

                $key = implode("\0", $values);

                $matches = array_merge($matches, $dictionary[$key] ?? []);
            }

            $collection = $this->related->newCollection($matches);

            $model->setRelation($relation, $collection);
        }

        return $models;
    }

    /**
     * Build model dictionary keyed by the relation's composite foreign key.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, TRelatedModel> $results
     * @return array<string, list<TRelatedModel>>
     */
    protected function buildDictionaryWithCompositeKey(Collection $results): array
    {
        /** @var list<string> $ownerKey */
        $ownerKey = $this->ownerKey;

        $dictionary = [];

        foreach ($results as $result) {
            $values = array_map(
                fn (string $key) => $result->$key,
                $ownerKey
            );

            $values = implode("\0", $values);

            $dictionary[$values][] = $result;
        }

        return $dictionary;
    }

    /**
     * Add the constraints for a relationship query for a composite key.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @return void
     */
    public function getRelationExistenceQueryWithCompositeKey(Builder $query): void
    {
        /** @var list<string> $foreignKey */
        $foreignKey = $this->foreignKey;

        $columns = array_slice($foreignKey, 1, preserve_keys: true);

        foreach ($columns as $i => $column) {
            $query->whereColumn(
                $this->child->qualifyColumn($column),
                '=',
                $query->qualifyColumn($this->ownerKey[$i])
            );
        }
    }
}
