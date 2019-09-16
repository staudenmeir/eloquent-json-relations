<?php

namespace Staudenmeir\EloquentJsonRelations\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use RuntimeException;
use Staudenmeir\EloquentJsonRelations\Grammars\MySqlGrammar;
use Staudenmeir\EloquentJsonRelations\Grammars\PostgresGrammar;
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
     * Create a new JSON relationship instance.
     *
     * @return void
     */
    public function __construct()
    {
        $args = func_get_args();

        $foreignKey = explode('[]->', $args[2]);

        $this->path = $foreignKey[0];
        $this->key = $foreignKey[1] ?? null;

        parent::__construct(...$args);
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

        if ($this->key && !empty($this->parent->getAttributes())) {
            $this->hydratePivotRelation($models, $this->parent);
        }

        return $models;
    }

    /**
     * Hydrate the pivot relationship on the models.
     *
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @return void
     */
    protected function hydratePivotRelation(Collection $models, Model $parent)
    {
        foreach ($models as $i => $model) {
            $clone = clone $model;

            $models[$i] = $clone->setRelation('pivot', $this->pivotRelation($clone, $parent));
        }
    }

    /**
     * Get the pivot relationship from the query.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function pivotRelation(Model $model, Model $parent)
    {
        $attributes = $this->pivotAttributes($model, $parent);

        return Pivot::fromAttributes($model, $attributes, null, true);
    }

    /**
     * Get the pivot attributes from a model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @return array
     */
    abstract protected function pivotAttributes(Model $model, Model $parent);

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
     * Get the JSON grammar.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Staudenmeir\EloquentJsonRelations\Grammars\JsonGrammar
     */
    protected function getJsonGrammar(Builder $query)
    {
        $driver = $query->getConnection()->getDriverName();

        switch ($driver) {
            case 'mysql':
                return $query->getConnection()->withTablePrefix(new MySqlGrammar);
            case 'pgsql':
                return $query->getConnection()->withTablePrefix(new PostgresGrammar);
            case 'sqlsrv':
                return $query->getConnection()->withTablePrefix(new SqlServerGrammar);
        }

        throw new RuntimeException('This database is not supported.'); // @codeCoverageIgnore
    }
}
