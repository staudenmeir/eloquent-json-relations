<?php

namespace Staudenmeir\EloquentJsonRelations\Grammars;

use Illuminate\Database\Query\Grammars\MySqlGrammar as Base;

class MySqlGrammar extends Base implements JsonGrammar
{
    /**
     * Compile a "JSON array" statement into SQL.
     *
     * @param string $column
     * @return string
     */
    public function compileJsonArray($column)
    {
        return 'json_array('.$this->wrap($column).')';
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
        return str_repeat('json_object(?, ', $levels)
                .$this->wrap($column)
                .str_repeat(')', $levels);
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

    public function compileJsonTable(string $selector, string $table, string $tableAlias, string $columnAlias): string
    {
        $segments = explode('[*]->', $selector);

        [$column, $path] = $this->wrapJsonFieldAndPath($segments[0] . '[*]');

        $table = $this->wrapTable($table); // TODO: qualify
        $tableAlias = $this->wrapTable($tableAlias);
        $columnAlias = $this->wrap($columnAlias);

        // TODO: error handling
        return <<<SQL
$table, json_table(
    $column$path columns($columnAlias json path '$' ERROR ON ERROR)
) as $tableAlias
SQL;
    }
}
