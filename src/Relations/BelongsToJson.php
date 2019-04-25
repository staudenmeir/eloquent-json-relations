<?php

namespace Staudenmeir\EloquentJsonRelations\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class BelongsToJson extends BelongsTo
{
    use InteractsWithPivotRecords, IsJsonRelation;

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return !empty($this->getForeignKeys())
            ? $this->get()
            : $this->related->newCollection();
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $table = $this->related->getTable();

            $this->query->whereIn($table.'.'.$this->ownerKey, $this->getForeignKeys());
        }
    }

    /**
     * Gather the keys from an array of related models.
     *
     * @param array $models
     * @return array
     */
    protected function getEagerModelKeys(array $models)
    {
        $keys = [];

        foreach ($models as $model) {
            $keys = array_merge($keys, (array) $model->{$this->foreignKey});
        }

        sort($keys);

        return array_values(array_unique($keys));
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param array  $models
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @param string $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            $matches = [];

            foreach ((array) $model->{$this->foreignKey} as $id) {
                if (isset($dictionary[$id])) {
                    $matches[] = $dictionary[$id];
                }
            }

            $model->setRelation($relation, $collection = $this->related->newCollection($matches));

            if ($this->key) {
                $this->hydratePivotRelation($collection, $model);
            }
        }

        return $models;
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->{$this->ownerKey}] = $result;
        }

        return $dictionary;
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
        if ($parentQuery->getQuery()->from == $query->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        $ownerKey = $this->relationExistenceQueryOwnerKey($query, $this->ownerKey);

        return $query->select($columns)->whereJsonContains(
            $this->getQualifiedPath(),
            $query->getQuery()->connection->raw($ownerKey)
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

        $query->getModel()->setTable($hash);

        $ownerKey = $this->relationExistenceQueryOwnerKey($query, $hash.'.'.$this->ownerKey);

        return $query->select($columns)->whereJsonContains(
            $this->getQualifiedPath(),
            $query->getQuery()->connection->raw($ownerKey)
        );
    }

    /**
     * Get the owner key for the relationship query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $ownerKey
     * @return string
     */
    protected function relationExistenceQueryOwnerKey(Builder $query, $ownerKey)
    {
        $ownerKey = $query->qualifyColumn($ownerKey);

        if (!$this->key) {
            return $this->getJsonGrammar($query)->compileJsonArray($ownerKey);
        }

        $query->addBinding($keys = explode('->', $this->key));

        return $this->getJsonGrammar($query)->compileJsonObject($ownerKey, count($keys));
    }

    /**
     * Get the pivot attributes from a model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @return array
     */
    protected function pivotAttributes(Model $model, Model $parent)
    {
        $key = str_replace('->', '.', $this->key);

        $record = collect($parent->{$this->path})
            ->filter(function ($value) use ($key, $model) {
                return Arr::get($value, $key) == $model->{$this->ownerKey};
            })->first();

        return Arr::except($record, $key);
    }

    /**
     * Get the foreign key values.
     *
     * @return array
     */
    public function getForeignKeys()
    {
        return (array) $this->child->{$this->foreignKey};
    }
}
