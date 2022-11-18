<?php

namespace Staudenmeir\EloquentJsonRelations\Grammars;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\PostgresGrammar as Base;

class PostgresGrammar extends Base implements JsonGrammar
{
    /**
     * Compile a "JSON array" statement into SQL.
     *
     * @param string $column
     * @return string
     */
    public function compileJsonArray($column)
    {
        return 'jsonb_build_array('.$this->wrap($column).')';
    }

    /**
     * Compile a "JSON object" statement into SQL.
     *
     * @param string $column
     * @param int $levels
     * @return string
     */
    public function compileJsonObject($column, $levels)
    {
        $sql = str_repeat('jsonb_build_object(?::text, ', $levels)
                .$this->wrap($column)
                .str_repeat(')', $levels);

        return $this->compileJsonArray(new Expression($sql));
    }

    /**
     * Compile a "JSON value select" statement into SQL.
     *
     * @param string $column
     * @return string
     */
    public function compileJsonValueSelect(string $column): string
    {
        return $this->wrap($column);
    }
}
