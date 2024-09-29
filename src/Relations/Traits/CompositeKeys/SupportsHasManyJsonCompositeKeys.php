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
trait SupportsHasManyJsonCompositeKeys
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
        /** @var list<string> $localKey */
        $localKey = $this->localKey;

        $columns = array_slice($localKey, 1);

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
     * @param list<TDeclaringModel> $models
     * @return void
     */
    protected function addEagerConstraintsWithCompositeKey(array $models): void
    {
        /** @var list<string> $foreignKey */
        $foreignKey = $this->foreignKey;

        /** @var list<string> $localKey */
        $localKey = $this->localKey;

        $keys = (new BaseCollection($models))->map(
            function (Model $model) use ($localKey) {
                return array_map(
                    fn (string $column) => $model[$column],
                    $localKey
                );
            }
        )->values()->unique(null, true)->all();

        $this->query->where(
            function (Builder $query) use ($foreignKey, $keys) {
                foreach ($keys as $key) {
                    $query->orWhere(
                        function (Builder $query) use ($foreignKey, $key) {
                            foreach ($foreignKey as $i => $column) {
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
     * @param list<TDeclaringModel> $models
     * @param \Illuminate\Database\Eloquent\Collection<int, TRelatedModel> $results
     * @param string $relation
     * @param string $type
     * @return list<TDeclaringModel>
     */
    protected function matchWithCompositeKey(array $models, Collection $results, string $relation, string $type): array
    {
        /** @var list<string> $localKey */
        $localKey = $this->localKey;

        $dictionary = $this->buildDictionaryWithCompositeKey($results);

        foreach ($models as $model) {
            $values = array_map(
                fn ($key) => $model->$key,
                $localKey
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
     * @param \Illuminate\Database\Eloquent\Collection<int, TRelatedModel> $results
     * @return array<string, list<TRelatedModel>>
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
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
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
     * @return array<int, string>
     */
    protected function getAdditionalForeignKeyNames(): array
    {
        /** @var list<string> $foreignKey */
        $foreignKey = $this->foreignKey;

        $names = [];

        $columns = array_slice($foreignKey, 1, preserve_keys: true);

        foreach ($columns as $i => $column) {
            $segments = explode('.', $column);

            $names[$i] = end($segments);
        }

        return $names;
    }
}
