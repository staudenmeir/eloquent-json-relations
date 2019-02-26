<?php

namespace Staudenmeir\EloquentJsonRelations\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class HasManyJson extends HasMany
{
    use IsJsonRelation;

    /**
     * Get the pivot attributes from a model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return array
     */
    protected function pivotAttributes(Model $model, Model $parent)
    {
        $record = collect($model->{$this->getPathName()})
            ->where($this->key, $parent->{$this->localKey})
            ->first();

        return Arr::except($record, $this->key);
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
                $parentKey = [[$this->key => $parentKey]];
            }

            $this->query->whereJsonContains($this->path, $parentKey);
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $keys = $this->getKeys($models, $this->localKey);

        $this->query->where(function (Builder $query) use ($keys) {
            foreach ($keys as $key) {
                if ($this->key) {
                    $key = [[$this->key => $key]];
                }

                $query->orWhereJsonContains($this->path, $key);
            }
        });
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @param  string  $type
     * @return array
     */
    protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
    {
        $models = parent::matchOneOrMany(...func_get_args());

        if ($this->key) {
            foreach ($models as $model) {
                $this->hydratePivotRelation($model->$relation, $model);
            }
        }

        return $models;
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $results
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
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    protected function setForeignAttributesForCreate(Model $model)
    {
        $foreignKey = explode('.', $this->foreignKey)[1];

        $relation = $model->belongsToJson(get_class($this->parent), $foreignKey, $this->localKey);

        $relation->attach($this->getParentKey());
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        if ($query->getQuery()->from == $parentQuery->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        $parentKey = $this->relationExistenceQueryParentKey($query);

        return $query->select($columns)->whereJsonContains(
            $this->getQualifiedPath(),
            $query->getQuery()->connection->raw($parentKey)
        );
    }

    /**
     * Add the constraints for a relationship query on the same table.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $query->from($query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash());

        $query->getModel()->setTable($hash);

        $parentKey = $this->relationExistenceQueryParentKey($query);

        return $query->select($columns)->whereJsonContains(
            $hash.'.'.$this->getPathName(),
            $query->getQuery()->connection->raw($parentKey)
        );
    }

    /**
     * Get the parent key for the relationship query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return string
     */
    protected function relationExistenceQueryParentKey(Builder $query)
    {
        $parentKey = $this->getQualifiedParentKeyName();

        if (! $this->key) {
            return $this->getJsonGrammar($query)->compileJsonArray($query->qualifyColumn($parentKey));
        }

        $this->query->addBinding($this->key);

        return $this->getJsonGrammar($query)->compileJsonObject($query->qualifyColumn($parentKey));

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
