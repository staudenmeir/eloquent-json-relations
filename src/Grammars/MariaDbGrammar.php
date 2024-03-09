<?php

namespace Staudenmeir\EloquentJsonRelations\Grammars;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\MariaDbGrammar as Base;
use Staudenmeir\EloquentJsonRelations\Grammars\Traits\CompilesMySqlJsonQueries;

class MariaDbGrammar extends Base implements JsonGrammar
{
    use CompilesMySqlJsonQueries;

    /**
     * Determine whether the database supports the "member of" operator.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @return bool
     */
    public function supportsMemberOf(ConnectionInterface $connection): bool
    {
        return false;
    }
}
