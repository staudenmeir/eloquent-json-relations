<?php

namespace Staudenmeir\EloquentJsonRelations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;
use Staudenmeir\EloquentJsonRelations\Relations\HasManyJson;
use Staudenmeir\EloquentJsonRelations\Relations\Postgres\BelongsTo as BelongsToPostgres;
use Staudenmeir\EloquentJsonRelations\Relations\Postgres\HasMany as HasManyPostgres;
use Staudenmeir\EloquentJsonRelations\Relations\Postgres\HasManyThrough as HasManyThroughPostgres;
use Staudenmeir\EloquentJsonRelations\Relations\Postgres\HasOne as HasOnePostgres;
use Staudenmeir\EloquentJsonRelations\Relations\Postgres\HasOneThrough as HasOneThroughPostgres;
use Staudenmeir\EloquentJsonRelations\Relations\Postgres\MorphMany as MorphManyPostgres;
use Staudenmeir\EloquentJsonRelations\Relations\Postgres\MorphOne as MorphOnePostgres;

trait HasJsonRelationships
{
    /**
     * Get an attribute from the model.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $attribute = preg_split('/(->|\[\])/', $key)[0];

        if (array_key_exists($attribute, $this->attributes)) {
            return $this->getAttributeValue($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * Get a plain attribute (not a relationship).
     *
     * @param string $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        if (Str::contains($key, '->')) {
            [$key, $path] = explode('->', $key, 2);

            if (substr($key, -2) === '[]') {
                $key = substr($key, 0, -2);

                $path = '*.'.$path;
            }

            $path = str_replace(['->', '[]'], ['.', '.*'], $path);

            return data_get($this->getAttributeValue($key), $path);
        }

        return parent::getAttributeValue($key);
    }

    /**
     * Instantiate a new HasOne relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    protected function newHasOne(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        if ($query->getConnection()->getDriverName() === 'pgsql') {
            return new HasOnePostgres($query, $parent, $foreignKey, $localKey);
        }

        return new HasOne($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Instantiate a new HasOneThrough relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $farParent
     * @param \Illuminate\Database\Eloquent\Model $throughParent
     * @param string $firstKey
     * @param string $secondKey
     * @param string $localKey
     * @param string $secondLocalKey
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    protected function newHasOneThrough(Builder $query, Model $farParent, Model $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey)
    {
        if ($query->getConnection()->getDriverName() === 'pgsql') {
            return new HasOneThroughPostgres($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
        }

        return new HasOneThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    /**
     * Instantiate a new MorphOne relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $type
     * @param string $id
     * @param string $localKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    protected function newMorphOne(Builder $query, Model $parent, $type, $id, $localKey)
    {
        if ($query->getConnection()->getDriverName() === 'pgsql') {
            return new MorphOnePostgres($query, $parent, $type, $id, $localKey);
        }

        return new MorphOne($query, $parent, $type, $id, $localKey);
    }

    /**
     * Instantiate a new BelongsTo relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $child
     * @param string $foreignKey
     * @param string $ownerKey
     * @param string $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    protected function newBelongsTo(Builder $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        if ($query->getConnection()->getDriverName() === 'pgsql') {
            return new BelongsToPostgres($query, $child, $foreignKey, $ownerKey, $relation);
        }

        return new BelongsTo($query, $child, $foreignKey, $ownerKey, $relation);
    }

    /**
     * Instantiate a new HasMany relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        if ($query->getConnection()->getDriverName() === 'pgsql') {
            return new HasManyPostgres($query, $parent, $foreignKey, $localKey);
        }

        return new HasMany($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Instantiate a new HasManyThrough relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $farParent
     * @param \Illuminate\Database\Eloquent\Model $throughParent
     * @param string $firstKey
     * @param string $secondKey
     * @param string $localKey
     * @param string $secondLocalKey
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    protected function newHasManyThrough(Builder $query, Model $farParent, Model $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey)
    {
        if ($query->getConnection()->getDriverName() === 'pgsql') {
            return new HasManyThroughPostgres($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
        }

        return new HasManyThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    /**
     * Instantiate a new MorphMany relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $type
     * @param string $id
     * @param string $localKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    protected function newMorphMany(Builder $query, Model $parent, $type, $id, $localKey)
    {
        if ($query->getConnection()->getDriverName() === 'pgsql') {
            return new MorphManyPostgres($query, $parent, $type, $id, $localKey);
        }

        return new MorphMany($query, $parent, $type, $id, $localKey);
    }

    /**
     * Define an inverse one-to-one or many JSON relationship.
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $ownerKey
     * @param string $relation
     * @return \Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson
     */
    public function belongsToJson($related, $foreignKey, $ownerKey = null, $relation = null)
    {
        if (is_null($relation)) {
            $relation = $this->guessBelongsToRelation();
        }

        /** @var \Illuminate\Database\Eloquent\Model $instance */
        $instance = $this->newRelatedInstance($related);

        $ownerKey = $ownerKey ?: $instance->getKeyName();

        return $this->newBelongsToJson(
            $instance->newQuery(), $this, $foreignKey, $ownerKey, $relation
        );
    }

    /**
     * Instantiate a new BelongsToJson relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $child
     * @param string $foreignKey
     * @param string $ownerKey
     * @param string $relation
     * @return \Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson
     */
    protected function newBelongsToJson(Builder $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        return new BelongsToJson($query, $child, $foreignKey, $ownerKey, $relation);
    }

    /**
     * Define a one-to-many JSON relationship.
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     * @return \Staudenmeir\EloquentJsonRelations\Relations\HasManyJson
     */
    public function hasManyJson($related, $foreignKey, $localKey = null)
    {
        /** @var \Illuminate\Database\Eloquent\Model $instance */
        $instance = $this->newRelatedInstance($related);

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasManyJson(
            $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey
        );
    }

    /**
     * Instantiate a new HasManyJson relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return \Staudenmeir\EloquentJsonRelations\Relations\HasManyJson
     */
    protected function newHasManyJson(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new HasManyJson($query, $parent, $foreignKey, $localKey);
    }
}
