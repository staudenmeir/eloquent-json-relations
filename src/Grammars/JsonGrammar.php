<?php

namespace Staudenmeir\EloquentJsonRelations\Grammars;

interface JsonGrammar
{
    /**
     * Compile a "JSON array" statement into SQL.
     *
     * @param string $column
     * @return string
     */
    public function compileJsonArray($column);

    /**
     * Compile a "JSON object" statement into SQL.
     *
     * @param string $column
     * @param int $levels
     * @return string
     */
    public function compileJsonObject($column, $levels);

    /**
     * Compile a "JSON value select" statement into SQL.
     *
     * @param string $column
     * @return string
     */
    public function compileJsonValueSelect(string $column): string;

    /**
     * TODO
     *
     * @param string $selector
     * @param string $table
     * @param string $tableAlias
     * @param string $columnAlias
     * @return string
     */
    public function compileJsonTable(string $selector, string $table, string $tableAlias, string $columnAlias): string;
}
