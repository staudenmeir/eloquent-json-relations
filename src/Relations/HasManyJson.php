<?php

namespace Staudenmeir\EloquentJsonRelations\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as BaseCollection;
use Staudenmeir\EloquentHasManyDeepContracts\Interfaces\ConcatenableRelation;
use Staudenmeir\EloquentJsonRelations\Relations\Traits\Concatenation\IsConcatenableHasManyJsonRelation;
use Staudenmeir\EloquentJsonRelations\Relations\Traits\IsJsonRelation;

class HasManyJson extends HasMany implements ConcatenableRelation
{
    use IsConcatenableHasManyJsonRelation;
    use IsJsonRelation;

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return !is_null($this->getParentKey())
            ? $this->get()
            : $this->related->newCollection();
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        $models = parent::get($columns);

        if ($this->key && !is_null($this->parent->{$this->localKey})) {
            $this->hydratePivotRelation(
                $models,
                $this->parent,
                fn (Model $model) => $model->{$this->getPathName()}
            );
        }

        return $models;
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $parentKey = $this->getParentKey();

            if ($this->key) {
                $parentKey = $this->parentKeyToArray($parentKey);
            }

            $this->query->whereJsonContains($this->path, $parentKey);
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $parentKeys = $this->getKeys($models, $this->localKey);

        $this->query->where(function (Builder $query) use ($parentKeys) {
            foreach ($parentKeys as $parentKey) {
                if ($this->key) {
                    $parentKey = $this->parentKeyToArray($parentKey);
                }

                $query->orWhereJsonContains($this->path, $parentKey);
            }
        });
    }

    /**
     * Embed a parent key in a nested array.
     *
     * @param mixed $parentKey
     * @return array
     */
    protected function parentKeyToArray($parentKey)
    {
        $keys = explode('->', $this->key);

        foreach (array_reverse($keys) as $key) {
            $parentKey = [$key => $parentKey];
        }

        return [$parentKey];
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param array  $models
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @param string $relation
     * @param string $type
     * @return array
     */
    protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
    {
        $models = parent::matchOneOrMany(...func_get_args());

        if ($this->key) {
            foreach ($models as $model) {
                $this->hydratePivotRelation(
                    $model->$relation,
                    $model,
                    fn (Model $model) => $model->{$this->getPathName()}
                );
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
        $foreign = $this->getForeignKeyName();

        $dictionary = [];

        foreach ($results as $result) {
            foreach ($result->{$foreign} as $value) {
                $dictionary[$value][] = $result;
            }
        }

        return $dictionary;
    }

    /**
     * Set the foreign ID for creating a related model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    protected function setForeignAttributesForCreate(Model $model)
    {
        $foreignKey = explode('.', $this->foreignKey)[1];

        /** @var \Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson $relation */
        $relation = $model->belongsToJson(get_class($this->parent), $foreignKey, $this->localKey);

        $relation->attach($this->getParentKey());
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
        if ($query->getQuery()->from == $parentQuery->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        [$sql, $bindings] = $this->relationExistenceQueryParentKey($query);

        $query->addBinding($bindings);

        return $query->select($columns)->whereJsonContains(
            $this->getQualifiedPath(),
            $query->getQuery()->connection->raw($sql)
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

        [$sql, $bindings] = $this->relationExistenceQueryParentKey($query);

        $query->addBinding($bindings);

        return $query->select($columns)->whereJsonContains(
            $hash.'.'.$this->getPathName(),
            $query->getQuery()->connection->raw($sql)
        );
    }

    /**
     * Get the parent key for the relationship query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    protected function relationExistenceQueryParentKey(Builder $query): array
    {
        $parentKey = $this->getQualifiedParentKeyName();

        if ($this->key) {
            $keys = explode('->', $this->key);

            $sql = $this->getJsonGrammar($query)->compileJsonObject($parentKey, count($keys));

            $bindings = $keys;
        } else {
            $sql = $this->getJsonGrammar($query)->compileJsonArray($parentKey);

            $bindings = [];
        }

        return [$sql, $bindings];
    }

    /**
     * Get the pivot attributes from a model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param array $records
     * @return array
     */
    public function pivotAttributes(Model $model, Model $parent, array $records)
    {
        $key = str_replace('->', '.', $this->key);

        $record = (new BaseCollection($records))
            ->filter(function ($value) use ($key, $parent) {
                return Arr::get($value, $key) == $parent->{$this->localKey};
            })->first();

        return Arr::except($record, $key);
    }

    /**
     * Get the plain path name.
     *
     * @return string
     */
    public function getPathName()
    {
        return last(explode('.', $this->path));
    }
}
