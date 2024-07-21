<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Traits;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use RuntimeException;
use Staudenmeir\EloquentJsonRelations\Grammars\JsonGrammar;
use Staudenmeir\EloquentJsonRelations\Grammars\MySqlGrammar;
use Staudenmeir\EloquentJsonRelations\Grammars\PostgresGrammar;
use Staudenmeir\EloquentJsonRelations\Grammars\SQLiteGrammar;
use Staudenmeir\EloquentJsonRelations\Grammars\SqlServerGrammar;

trait IsJsonRelation
{
    /**
     * The base path of the foreign key.
     *
     * @var string
     */
    protected $path;

    /**
     * The optional object key of the foreign key.
     *
     * @var string
     */
    protected $key;

    /**
     * Hydrate the pivot relationship on the models.
     *
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param callable $callback
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function hydratePivotRelation(Collection $models, Model $parent, callable $callback): Collection
    {
        foreach ($models as $i => $model) {
            $clone = clone $model;

            $models[$i] = $clone->setRelation(
                $this->getPivotAccessor(),
                $this->pivotRelation($clone, $parent, $callback)
            );
        }

        return $models;
    }

    /**
     * Get the pivot relationship from the query.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param callable $callback
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function pivotRelation(Model $model, Model $parent, callable $callback)
    {
        $records = $callback($model, $parent);

        if ($records instanceof Arrayable) {
            $records = $records->toArray();
        }

        $attributes = $this->pivotAttributes($model, $parent, $records);

        return Pivot::fromAttributes($model, $attributes, null, true);
    }

    /**
     * Get the pivot attributes from a model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param array $records
     * @return array
     */
    abstract public function pivotAttributes(Model $model, Model $parent, array $records);

    /**
     * Execute the query and get the first related model.
     *
     * @param array $columns
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Get the fully qualified path of the relationship.
     *
     * @return string
     */
    public function getQualifiedPath()
    {
        return $this->parent->qualifyColumn($this->path);
    }

    /**
     * Add a “where JSON contains” or "member of" clause to the query.
     *
     * @param \Illuminate\Contracts\Database\Query\Builder $query
     * @param string $column
     * @param mixed $value
     * @param callable|null $objectValueCallback
     * @param string $boolean
     * @return void
     */
    protected function whereJsonContainsOrMemberOf(
        Builder $query,
        string $column,
        mixed $value,
        callable $objectValueCallback = null,
        string $boolean = 'and'
    ): void {
        $grammar = $this->getJsonGrammar($query);
        $connection = $query->getConnection();

        if ($grammar->supportsMemberOf($connection)) {
            $query->whereRaw(
                $grammar->compileMemberOf($column, $this->key, $value),
                $grammar->prepareBindingsForMemberOf($value),
                $boolean
            );
        } else {
            $value = $this->key && $objectValueCallback ? $objectValueCallback($value) : $value;

            $query->whereJsonContains($column, $value, $boolean);
        }
    }

    /**
     * Get the JSON grammar.
     *
     * @param \Illuminate\Contracts\Database\Query\Builder $query
     * @return \Staudenmeir\EloquentJsonRelations\Grammars\JsonGrammar
     */
    protected function getJsonGrammar(Builder $query): JsonGrammar
    {
        $driver = $query->getConnection()->getDriverName();

        return $query->getConnection()->withTablePrefix(
            match ($driver) {
                'mysql' => new MySqlGrammar(),
                'pgsql' => new PostgresGrammar(),
                'sqlite' => new SQLiteGrammar(),
                'sqlsrv' => new SqlServerGrammar(),
                default => throw new RuntimeException('This database is not supported.') // @codeCoverageIgnore
            }
        );
    }

    /**
     * Get the name of the pivot accessor for this relationship.
     *
     * @return string
     */
    public function getPivotAccessor(): string
    {
        return 'pivot';
    }

    /**
     * Get the base path of the foreign key.
     *
     * @return string
     */
    public function getForeignKeyPath(): string
    {
        return $this->path;
    }
}
