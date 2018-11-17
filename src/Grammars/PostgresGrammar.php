<?php

namespace Staudenmeir\EloquentJsonRelations\Grammars;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\PostgresGrammar as Base;

class PostgresGrammar extends Base implements JsonGrammar
{
    /**
     * Compile a "JSON array" statement into SQL.
     *
     * @param  string  $column
     * @return string
     */
    public function compileJsonArray($column)
    {
        return 'jsonb_build_array('.$this->wrap($column).')';
    }

    /**
     * Compile a "JSON object" statement into SQL.
     *
     * @param  string  $column
     * @return string
     */
    public function compileJsonObject($column)
    {
        $sql = 'jsonb_build_object(?::text, '.$this->wrap($column).')';

        return $this->compileJsonArray(new Expression($sql));
    }
}
