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

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Relations\HasMany<TRelatedModel, TDeclaringModel>
 */
class HasManyJson extends HasMany implements ConcatenableRelation
{
    /** @use \Staudenmeir\EloquentJsonRelations\Relations\Traits\Concatenation\IsConcatenableHasManyJsonRelation<TRelatedModel, TDeclaringModel> */
    use IsConcatenableHasManyJsonRelation;
    /** @use \Staudenmeir\EloquentJsonRelations\Relations\Traits\IsJsonRelation<TRelatedModel, TDeclaringModel> */
    use IsJsonRelation;
    /** @use \Staudenmeir\EloquentJsonRelations\Relations\Traits\CompositeKeys\SupportsHasManyJsonCompositeKeys<TRelatedModel, TDeclaringModel> */
    use SupportsHasManyJsonCompositeKeys;

    /**
     * Create a new has many JSON relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @param TDeclaringModel $parent
     * @param list<string>|string $foreignKey
     * @param list<string>|string $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        $segments = is_array($foreignKey)
            ? explode('[]->', $foreignKey[0])
            : explode('[]->', $foreignKey);

        $this->path = $segments[0];
        $this->key = $segments[1] ?? null;

        // @phpstan-ignore-next-line
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
     * @param list<string> $columns
     * @return \Illuminate\Database\Eloquent\Collection<int, TRelatedModel>
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

    /** @inheritDoc */
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
     * @return array<array<mixed>>
     */
    protected function parentKeyToArray($parentKey)
    {
        /** @var string $key */
        $key = $this->key;

        $keys = explode('->', $key);

        foreach (array_reverse($keys) as $key) {
            $parentKey = [$key => $parentKey];
        }

        return [$parentKey];
    }

    /** @inheritDoc */
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

    /** @inheritDoc */
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

        if (method_exists($model, 'belongsToJson')) {
            /** @var \Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson<*, *> $relation */
            $relation = $model->belongsToJson(get_class($this->parent), $foreignKey, $this->localKey);

            $relation->attach($this->getParentKey());
        }
    }

    /** @inheritDoc */
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


        $query->select($columns);

        return $query;
    }

    /** @inheritDoc */
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


        $query->select($columns);

        return $query;
    }

    /**
     * Get the parent key for the relationship query.
     *
     * @param \Illuminate\Database\Eloquent\Builder<TRelatedModel> $query
     * @return array{0: string, 1: list<mixed>}
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
     * @param TRelatedModel $model
     * @param TDeclaringModel $parent
     * @param list<array<string, mixed>> $records
     * @return array<string, mixed>
     */
    public function pivotAttributes(Model $model, Model $parent, array $records)
    {
        /** @var string $key */
        $key = $this->key;

        $key = str_replace('->', '.', $key);

        $localKey = $this->hasCompositeKey() ? $this->localKey[0] : $this->localKey;

        /** @var array<string, mixed> $record */
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
        /** @var string $pathName */
        $pathName = last(
            explode('.', $this->path)
        );

        return $pathName;
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
