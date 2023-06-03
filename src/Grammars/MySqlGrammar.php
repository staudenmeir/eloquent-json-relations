<?php

namespace Staudenmeir\EloquentJsonRelations\Grammars;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\MySqlGrammar as Base;
use PDO;

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

    /**
     * Determine whether the database supports the "member of" operator.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @return bool
     */
    public function supportsMemberOf(ConnectionInterface $connection): bool
    {
        if ($connection->isMaria()) {
            return false;
        }

        $version = $connection->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

        return version_compare($version, '8.0.17') >= 0;
    }

    /**
     * Compile a "member of" statement into SQL.
     *
     * @param string $column
     * @param mixed $value
     * @return string
     */
    public function compileMemberOf(string $column, ?string $objectKey, mixed $value): string
    {
        $columnWithKey = $objectKey ? $column . (str_contains($column, '->') ? '[*]' : '') . "->$objectKey" : $column;

        [$field, $path] = $this->wrapJsonFieldAndPath($columnWithKey);

        if ($objectKey && !str_contains($column, '->')) {
            $path = ", '$[*]" . substr($path, 4);
        }

        $sql = $path ? "json_extract($field$path)" : $field;

        if ($value instanceof Expression) {
            return $value->getValue($this) . " member of($sql)";
        }

        return "? member of($sql)";
    }

    /**
     * Prepare the bindings for a "member of" statement.
     *
     * @param mixed $value
     * @return array
     */
    public function prepareBindingsForMemberOf(mixed $value): array
    {
        return $value instanceof Expression ? [] : [$value];
    }
}
