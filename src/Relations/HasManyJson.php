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
use Staudenmeir\EloquentJsonRelations\Relations\Traits\CompositeKeys\SupportsHasManyJsonCompositeKeys;
use Staudenmeir\EloquentJsonRelations\Relations\Traits\IsJsonRelation;

class HasManyJson extends HasMany implements ConcatenableRelation
{
    use IsConcatenableHasManyJsonRelation;
    use IsJsonRelation;
    use SupportsHasManyJsonCompositeKeys;

    /**
     * The foreign key of the parent model.
     *
     * @var string|array
     */
    protected $foreignKey;

    /**
     * The local key of the parent model.
     *
     * @var string|array
     */
    protected $localKey;

    /**
     * Create a new has many JSON relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        $segments = is_array($foreignKey)
            ? explode('[]->', $foreignKey[0])
            : explode('[]->', $foreignKey);

        $this->path = $segments[0];
        $this->key = $segments[1] ?? null;

        parent::__construct($query, $parent, $foreignKey, $localKey);
    }

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

        $localKey = $this->hasCompositeKey() ? $this->localKey[0] : $this->localKey;

        if ($this->key && !is_null($this->parent->$localKey)) {
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

            $this->whereJsonContainsOrMemberOf(
                $this->query,
                $this->path,
                $parentKey,
                fn ($parentKey) => $this->parentKeyToArray($parentKey)
            );

            if ($this->hasCompositeKey()) {
                $this->addConstraintsWithCompositeKey();
            }
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
        if ($this->hasCompositeKey()) {
            $this->addEagerConstraintsWithCompositeKey($models);

            return;
        }

        $parentKeys = $this->getKeys($models, $this->localKey);

        $this->query->where(function (Builder $query) use ($parentKeys) {
            foreach ($parentKeys as $parentKey) {
                $this->whereJsonContainsOrMemberOf(
                    $query,
                    $this->path,
                    $parentKey,
                    fn ($parentKey) => $this->parentKeyToArray($parentKey),
                    'or'
                );
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
     * @param array $models
     * @param \Illuminate\Database\Eloquent\Collection $results
     * @param string $relation
     * @param string $type
     * @return array
     */
    protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
    {
        if ($this->hasCompositeKey()) {
            $this->matchWithCompositeKey($models, $results, $relation, 'many');
        } else {
            parent::matchOneOrMany($models, $results, $relation, $type);
        }

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

        $this->whereJsonContainsOrMemberOf(
            $query,
            $this->getQualifiedPath(),
            $query->getQuery()->connection->raw($sql)
        );

        if ($this->hasCompositeKey()) {
            $this->getRelationExistenceQueryWithCompositeKey($query);
        }

        return $query->select($columns);
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
        $query->from($query->getModel()->getTable() . ' as ' . $hash = $this->getRelationCountHash());

        $query->getModel()->setTable($hash);

        [$sql, $bindings] = $this->relationExistenceQueryParentKey($query);

        $query->addBinding($bindings);

        $this->whereJsonContainsOrMemberOf(
            $query,
            $hash . '.' . $this->getPathName(),
            $query->getQuery()->connection->raw($sql)
        );

        return $query->select($columns);
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

        $grammar = $this->getJsonGrammar($query);
        $connection = $query->getConnection();

        if ($grammar->supportsMemberOf($connection)) {
            $sql = $grammar->wrap($parentKey);

            $bindings = [];
        } else {
            if ($this->key) {
                $keys = explode('->', $this->key);

                $sql = $this->getJsonGrammar($query)->compileJsonObject($parentKey, count($keys));

                $bindings = $keys;
            } else {
                $sql = $this->getJsonGrammar($query)->compileJsonArray($parentKey);

                $bindings = [];
            }
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

        $localKey = $this->hasCompositeKey() ? $this->localKey[0] : $this->localKey;

        $record = (new BaseCollection($records))
            ->filter(function ($value) use ($key, $localKey, $parent) {
                return Arr::get($value, $key) == $parent->$localKey;
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

    /**
     * Get the key value of the parent's local key.
     *
     * @return mixed
     */
    public function getParentKey()
    {
        $localKey = $this->hasCompositeKey() ? $this->localKey[0] : $this->localKey;

        return $this->parent->getAttribute($localKey);
    }

    /**
     * Get the fully qualified parent key name.
     *
     * @return string
     */
    public function getQualifiedParentKeyName()
    {
        $localKey = $this->hasCompositeKey() ? $this->localKey[0] : $this->localKey;

        return $this->parent->qualifyColumn($localKey);
    }

    /**
     * Get the foreign key for the relationship.
     *
     * @return string
     */
    public function getQualifiedForeignKeyName()
    {
        return $this->hasCompositeKey() ? $this->foreignKey[0] : $this->foreignKey;
    }
}
