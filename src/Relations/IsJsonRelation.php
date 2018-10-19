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
