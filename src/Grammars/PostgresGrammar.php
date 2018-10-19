<?php

namespace Staudenmeir\EloquentJsonRelations\Grammars;

use Illuminate\Database\Query\Grammars\PostgresGrammar as BaseGrammar;

class PostgresGrammar extends BaseGrammar implements JsonGrammar
{
    /**
     * Compile a "cast as JSON" statement into SQL.
     *
     * @param  string  $column
     * @return string
     */
    public function compileCastAsJson($column)
    {
        return $this->wrap($column).'::text::jsonb';
    }
}
