<?php

namespace Staudenmeir\EloquentJsonRelations\Grammars;

use Illuminate\Database\Query\Grammars\SqlServerGrammar as Base;
use RuntimeException;

class SqlServerGrammar extends Base implements JsonGrammar
{
    /**
     * Compile a "JSON array" statement into SQL.
     *
     * @param string $column
     * @return string
     */
    public function compileJsonArray($column)
    {
        return $this->wrap($column);
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
        throw new RuntimeException('This database is not supported.'); // @codeCoverageIgnore
    }

    /**
     * Compile a "JSON value select" statement into SQL.
     *
     * @param string $column
     * @return string
     */
    public function compileJsonValueSelect(string $column): string
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($column);

        return "json_query($field$path)";
    }
}
