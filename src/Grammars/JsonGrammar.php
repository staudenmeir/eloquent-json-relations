<?php

namespace Staudenmeir\EloquentJsonRelations\Grammars;

interface JsonGrammar
{
    /**
     * Compile a "cast as JSON" statement into SQL.
     *
     * @param  string  $column
     * @return string
     */
    public function compileCastAsJson($column);
}
