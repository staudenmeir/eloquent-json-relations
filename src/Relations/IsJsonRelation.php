<?php

namespace Staudenmeir\EloquentJsonRelations\Relations;

use Illuminate\Database\Eloquent\Builder;
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
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Staudenmeir\EloquentJsonRelations\Grammars\JsonGrammar
     */
    protected function getJsonGrammar(Builder $query) {
        $driver = $query->getConnection()->getDriverName();

        switch ($driver) {
            case 'mysql':
                return new MySqlGrammar;
            case 'pgsql':
                return new PostgresGrammar;
            case 'sqlsrv':
                return new SqlServerGrammar;
        }

        throw new RuntimeException('This database is not supported.');
    }
}
