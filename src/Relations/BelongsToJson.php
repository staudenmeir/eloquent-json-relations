<?php

namespace Staudenmeir\EloquentJsonRelations\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection as BaseCollection;

class BelongsToJson extends BelongsTo
{
    use IsJsonRelation;

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->query->get();
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

            $this->query->whereIn($table.'.'.$this->ownerKey, (array) $this->child->{$this->foreignKey});
        }
    }

    /**
     * Gather the keys from an array of related models.
     *
     * @param  array  $models
     * @return array
     */
    protected function getEagerModelKeys(array $models)
    {
        $keys = [];

        foreach ($models as $model) {
            $keys = array_merge($keys, (array) $model->{$this->foreignKey});
        }

        if (count($keys) === 0) {
            return [null];
        }

        sort($keys);

        return array_values(array_unique($keys));
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $foreign = $this->foreignKey;

        $owner = $this->ownerKey;

        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->getAttribute($owner)] = $result;
        }

        foreach ($models as $model) {
            $matches = [];

            foreach ((array) $model->$foreign as $id) {
                if (isset($dictionary[$id])) {
                    $matches[] = $dictionary[$id];
                }
            }

            $model->setRelation($relation, $this->related->newCollection($matches));
        }

        return $models;
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
        if ($parentQuery->getQuery()->from == $query->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        $ownerKey = $this->getJsonGrammar($query)->compileCastAsJson($query->qualifyColumn($this->ownerKey));

        return $query->select($columns)->whereJsonContains(
            $this->getQualifiedForeignKey(),
            $query->getQuery()->connection->raw($ownerKey)
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
        $query->select($columns)->from(
            $query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash()
        );

        $query->getModel()->setTable($hash);

        $ownerKey = $this->getJsonGrammar($query)->compileCastAsJson($query->qualifyColumn($hash.'.'.$this->ownerKey));

        return $query->whereJsonContains(
            $this->getQualifiedForeignKey(),
            $query->getQuery()->connection->raw($ownerKey)
        );
    }

    /**
     * Attach models to the relationship.
     *
     * @param  mixed  $ids
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function attach($ids)
    {
        $this->child->{$this->foreignKey} = array_values(
            array_unique(
                array_merge(
                    (array) $this->child->{$this->foreignKey},
                    $this->parseIds($ids)
                )
            )
        );

        return $this->child;
    }

    /**
     * Detach models from the relationship.
     *
     * @param  mixed  $ids
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function detach($ids = null)
    {
        if (! is_null($ids)) {
            $this->child->{$this->foreignKey} = array_values(
                array_diff(
                    (array) $this->child->{$this->foreignKey},
                    $this->parseIds($ids)
                )
            );
        } else {
            $this->child->{$this->foreignKey} = [];
        }

        return $this->child;
    }

    /**
     * Sync the relationship with a list of models.
     *
     * @param  mixed  $ids
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function sync($ids)
    {
        $this->child->{$this->foreignKey} = $this->parseIds($ids);

        return $this->child;
    }

    /**
     * Toggles models from the relationship.
     *
     * @param  mixed  $ids
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function toggle($ids)
    {
        $records = (array) $this->child->{$this->foreignKey};

        $this->child->{$this->foreignKey} = array_values(
            array_diff(
                array_merge($records, $ids),
                array_intersect($records, $this->parseIds($ids))
            )
        );

        return $this->child;
    }

    /**
     * Get all of the IDs from the given mixed value.
     *
     * @param  mixed  $value
     * @return array
     */
    protected function parseIds($value)
    {
        if ($value instanceof Model) {
            return [$value->{$this->ownerKey}];
        }

        if ($value instanceof Collection) {
            return $value->pluck($this->ownerKey)->all();
        }

        if ($value instanceof BaseCollection) {
            return $value->toArray();
        }

        return (array) $value;
    }
}
