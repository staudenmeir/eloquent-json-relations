<?php

namespace Staudenmeir\EloquentJsonRelations\Grammars;

use Illuminate\Database\Query\Grammars\MySqlGrammar as BaseGrammar;

class MySqlGrammar extends BaseGrammar implements JsonGrammar
{
    /**
     * Compile a "cast as JSON" statement into SQL.
     *
     * @param  string  $column
     * @return string
     */
    public function compileCastAsJson($column)
    {
        return 'cast('.$this->wrap($column).' as json)';
    }
}
