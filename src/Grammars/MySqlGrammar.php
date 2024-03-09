<?php

namespace Staudenmeir\EloquentJsonRelations\Grammars;

use Illuminate\Database\Query\Grammars\MySqlGrammar as Base;
use Staudenmeir\EloquentJsonRelations\Grammars\Traits\CompilesMySqlJsonQueries;

class MySqlGrammar extends Base implements JsonGrammar
{
    use CompilesMySqlJsonQueries;
}
