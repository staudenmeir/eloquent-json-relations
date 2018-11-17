<?php

namespace Staudenmeir\EloquentJsonRelations\Grammars;

use Illuminate\Database\Query\Grammars\MySqlGrammar as Base;

class MySqlGrammar extends Base implements JsonGrammar
{
    /**
     * Compile a "JSON array" statement into SQL.
     *
     * @param  string  $column
     * @return string
     */
    public function compileJsonArray($column)
    {
        return 'json_array('.$this->wrap($column).')';
    }

    /**
     * Compile a "JSON object" statement into SQL.
     *
     * @param  string  $column
     * @return string
     */
    public function compileJsonObject($column)
    {
        return 'json_object(?, '.$this->wrap($column).')';
    }
}
