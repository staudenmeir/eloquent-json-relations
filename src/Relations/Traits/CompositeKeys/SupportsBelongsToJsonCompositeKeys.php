<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Traits\CompositeKeys;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;

trait SupportsBelongsToJsonCompositeKeys
{
    /**
     * Determine whether the relationship has a composite key.
     *
     * @return bool
     */
    protected function hasCompositeKey(): bool
    {
        return is_array($this->foreignKey);
    }

    /**
     * Set the base constraints on the relation query for a composite key.
     *
     * @return void
     */
    protected function addConstraintsWithCompositeKey(): void
    {
        $columns = array_slice($this->ownerKey, 1);

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
     * @param array $models
     * @return void
     */
    protected function addEagerConstraintsWithCompositeKey(array $models): void
    {
        $keys = (new BaseCollection($models))->map(
            function (Model $model) {
                return array_map(
                    fn (string $column) => $model[$column],
                    $this->foreignKey
                );
            }
        )->values()->unique(null, true)->all();

        $this->query->where(
            function (Builder $query) use ($keys) {
                foreach ($keys as $key) {
                    $query->orWhere(
                        function (Builder $query) use ($key) {
                            foreach ($this->ownerKey as $i => $column) {
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
     * @param array $models
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @param string $relation
     * @return array
     */
    protected function matchWithCompositeKey(array $models, Collection $results, string $relation): array
    {
        $dictionary = $this->buildDictionaryWithCompositeKey($results);

        foreach ($models as $model) {
            $matches = [];

            $additionalValues = array_map(
                fn (string $key) => $model->$key,
                array_slice($this->ownerKey, 1)
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
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @return array
     */
    protected function buildDictionaryWithCompositeKey(Collection $results): array
    {
        $dictionary = [];

        foreach ($results as $result) {
            $values = array_map(
                fn (string $key) => $result->$key,
                $this->ownerKey
            );

            $values = implode("\0", $values);

            $dictionary[$values][] = $result;
        }

        return $dictionary;
    }

    /**
     * Add the constraints for a relationship query for a composite key.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function getRelationExistenceQueryWithCompositeKey(Builder $query): void
    {
        $columns = array_slice($this->foreignKey, 1, preserve_keys: true);

        foreach ($columns as $i => $column) {
            $query->whereColumn(
                $this->child->qualifyColumn($column),
                '=',
                $query->qualifyColumn($this->ownerKey[$i])
            );
        }
    }
}
