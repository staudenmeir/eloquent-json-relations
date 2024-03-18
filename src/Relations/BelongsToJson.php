<?php

namespace Staudenmeir\EloquentJsonRelations\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as BaseCollection;
use Staudenmeir\EloquentHasManyDeepContracts\Interfaces\ConcatenableRelation;
use Staudenmeir\EloquentJsonRelations\Relations\Traits\CompositeKeys\SupportsBelongsToJsonCompositeKeys;
use Staudenmeir\EloquentJsonRelations\Relations\Traits\Concatenation\IsConcatenableBelongsToJsonRelation;
use Staudenmeir\EloquentJsonRelations\Relations\Traits\IsJsonRelation;

class BelongsToJson extends BelongsTo implements ConcatenableRelation
{
    use InteractsWithPivotRecords;
    use IsConcatenableBelongsToJsonRelation;
    use IsJsonRelation;
    use SupportsBelongsToJsonCompositeKeys;

    /**
     * The foreign key of the parent model.
     *
     * @var string|array
     */
    protected $foreignKey;

    /**
     * The associated key on the parent model.
     *
     * @var string|array
     */
    protected $ownerKey;

    /**
     * Create a new belongs to JSON relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $child
     * @param string $foreignKey
     * @param string $ownerKey
     * @param string $relationName
     * @return void
     */
    public function __construct(Builder $query, Model $child, $foreignKey, $ownerKey, $relationName)
    {
        $segments = is_array($foreignKey)
            ? explode('[]->', $foreignKey[0])
            : explode('[]->', $foreignKey);

        $this->path = $segments[0];
        $this->key = $segments[1] ?? null;

        parent::__construct($query, $child, $foreignKey, $ownerKey, $relationName);
    }

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
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        $models = parent::get($columns);

        if ($this->key && !empty($this->parent->{$this->path})) {
            $this->hydratePivotRelation(
                $models,
                $this->parent,
                fn (Model $model, Model $parent) => $parent->{$this->path}
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
            $table = $this->related->getTable();

            $ownerKey = $this->hasCompositeKey() ? $this->ownerKey[0] : $this->ownerKey;

            $this->query->whereIn("$table.$ownerKey", $this->getForeignKeys());

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

        parent::addEagerConstraints($models);
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
            $keys = array_merge($keys, $this->getForeignKeys($model));
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
        if ($this->hasCompositeKey()) {
            $this->matchWithCompositeKey($models, $results, $relation);
        } else {
            $dictionary = $this->buildDictionary($results);

            foreach ($models as $model) {
                $matches = [];

                foreach ($this->getForeignKeys($model) as $id) {
                    if (isset($dictionary[$id])) {
                        $matches[] = $dictionary[$id];
                    }
                }

                $collection = $this->related->newCollection($matches);

                $model->setRelation($relation, $collection);
            }
        }

        foreach ($models as $model) {
            if ($this->key) {
                $this->hydratePivotRelation(
                    $model->getRelation($relation),
                    $model,
                    fn (Model $model, Model $parent) => $parent->{$this->path}
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

        $ownerKey = $this->hasCompositeKey() ? $this->ownerKey[0] : $this->ownerKey;

        [$sql, $bindings] = $this->relationExistenceQueryOwnerKey($query, $ownerKey);

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
        $query->from($query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash());

        $query->getModel()->setTable($hash);

        [$sql, $bindings] = $this->relationExistenceQueryOwnerKey($query, $hash.'.'.$this->ownerKey);

        $query->addBinding($bindings);

        $this->whereJsonContainsOrMemberOf(
            $query,
            $this->getQualifiedPath(),
            $query->getQuery()->connection->raw($sql)
        );

        return $query->select($columns);
    }

    /**
     * Get the owner key for the relationship query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $ownerKey
     * @return array
     */
    protected function relationExistenceQueryOwnerKey(Builder $query, string $ownerKey): array
    {
        $ownerKey = $query->qualifyColumn($ownerKey);

        $grammar = $this->getJsonGrammar($query);
        $connection = $query->getConnection();

        if ($grammar->supportsMemberOf($connection)) {
            $sql = $grammar->wrap($ownerKey);

            $bindings = [];
        } else {
            if ($this->key) {
                $keys = explode('->', $this->key);

                $sql = $this->getJsonGrammar($query)->compileJsonObject($ownerKey, count($keys));

                $bindings = $keys;
            } else {
                $sql = $this->getJsonGrammar($query)->compileJsonArray($ownerKey);

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

        $ownerKey = $this->hasCompositeKey() ? $this->ownerKey[0] : $this->ownerKey;

        $record = (new BaseCollection($records))
            ->filter(function ($value) use ($key, $model, $ownerKey) {
                return Arr::get($value, $key) == $model->$ownerKey;
            })->first();

        return Arr::except($record, $key);
    }

    /**
     * Get the foreign key values.
     *
     * @param \Illuminate\Database\Eloquent\Model|null $model
     * @return array
     */
    public function getForeignKeys(Model $model = null)
    {
        $model = $model ?: $this->child;

        $foreignKey = $this->hasCompositeKey() ? $this->foreignKey[0] : $this->foreignKey;

        return (new BaseCollection($model->$foreignKey))->filter(fn ($key) => $key !== null)->all();
    }

    /**
     * Get the related key for the relationship.
     *
     * @return string
     */
    public function getRelatedKeyName()
    {
        return $this->ownerKey;
    }
}
