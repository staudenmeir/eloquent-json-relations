<?php

namespace Staudenmeir\EloquentJsonRelations\Grammars;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\MariaDbGrammar as Base;
use Staudenmeir\EloquentJsonRelations\Grammars\Traits\CompilesMySqlJsonQueries;

class MariaDbGrammar extends Base implements JsonGrammar
{
    use CompilesMySqlJsonQueries;

    /** @inheritDoc */
    protected function wrapJsonSelector($value)
    {
        /** @var string $field */
        /** @var string $path */
        [$field, $path] = $this->wrapJsonFieldAndPath($value);

        // @see https://github.com/laravel/framework/pull/57417
        return 'case when json_type(json_extract('.$field.$path.")) in ('ARRAY', 'OBJECT') ".
            'then json_unquote(json_extract('.$field.$path.')) '.
            'else json_value('.$field.$path.') end';
    }

    /**
     * Determine whether the database supports the "member of" operator.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @return bool
     */
    public function supportsMemberOf(ConnectionInterface $connection): bool
    {
        return false;
    }
}
