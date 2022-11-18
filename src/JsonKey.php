<?php

namespace Staudenmeir\EloquentJsonRelations;

class JsonKey
{
    public function __construct(protected string $column)
    {
        //
    }

    public function __toString(): string
    {
        return $this->column;
    }
}
