<?php

namespace Staudenmeir\EloquentJsonRelations\Grammars;

use Illuminate\Database\ConnectionInterface;

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
     * Determine whether the database supports the "member of" operator.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @return bool
     */
    public function supportsMemberOf(ConnectionInterface $connection): bool;

    /**
     * Compile a "member of" statement into SQL.
     *
     * @param string $column
     * @param string|null $objectKey
     * @param mixed $value
     * @return string
     */
    public function compileMemberOf(string $column, ?string $objectKey, mixed $value): string;

    /**
     * Prepare the bindings for a "member of" statement.
     *
     * @param mixed $value
     * @return array
     */
    public function prepareBindingsForMemberOf(mixed $value): array;
}
