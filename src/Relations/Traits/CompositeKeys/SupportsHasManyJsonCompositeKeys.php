<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Traits\CompositeKeys;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;

trait SupportsHasManyJsonCompositeKeys
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
        $columns = array_slice($this->localKey, 1);

        foreach ($columns as $column) {
            $this->query->where(
                $this->related->qualifyColumn($column),
                '=',
                $this->parent->$column
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
                    $this->localKey
                );
            }
        )->values()->unique(null, true)->all();

        $this->query->where(
            function (Builder $query) use ($keys) {
                foreach ($keys as $key) {
                    $query->orWhere(
                        function (Builder $query) use ($key) {
                            foreach ($this->foreignKey as $i => $column) {
                                if ($i === 0) {
                                    $this->whereJsonContainsOrMemberOf(
                                        $query,
                                        $this->path,
                                        $key[$i],
                                        fn ($parentKey) => $this->parentKeyToArray($parentKey)
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
     * @param string $type
     * @return array
     */
    protected function matchWithCompositeKey(array $models, Collection $results, string $relation, string $type): array
    {
        $dictionary = $this->buildDictionaryWithCompositeKey($results);

        foreach ($models as $model) {
            $values = array_map(
                fn ($key) => $model->$key,
                $this->localKey
            );

            $key = implode("\0", $values);

            if (isset($dictionary[$key])) {
                $model->setRelation(
                    $relation,
                    $this->getRelationValue($dictionary, $key, $type)
                );
            }
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

        $foreignKey = $this->getForeignKeyName();

        $additionalColumns = $this->getAdditionalForeignKeyNames();

        foreach ($results as $result) {
            $additionalValues = array_map(
                fn (string $column) => $result->getAttribute($column),
                $additionalColumns
            );

            foreach($result->$foreignKey as $value) {
                $values = [$value, ...$additionalValues];

                $key = implode("\0", $values);

                $dictionary[$key][] = $result;
            }
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
        $columns = $this->getAdditionalForeignKeyNames();

        foreach ($columns as $i => $column) {
            $query->whereColumn(
                $this->parent->qualifyColumn($column),
                '=',
                $query->qualifyColumn($this->localKey[$i])
            );
        }
    }

    /**
     * Get the plain additional foreign keys.
     *
     * @return array
     */
    protected function getAdditionalForeignKeyNames(): array
    {
        $names = [];

        $columns = array_slice($this->foreignKey, 1, preserve_keys: true);

        foreach ($columns as $i => $column) {
            $segments = explode('.', $column);

            $names[$i] = end($segments);
        }

        return $names;
    }
}
