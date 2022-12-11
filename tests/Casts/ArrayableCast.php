<?php

namespace Tests\Casts;

use ArrayIterator;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;

class ArrayableCast extends ArrayIterator implements CastsAttributes, Arrayable
{
    public function get($model, string $key, $value, array $attributes)
    {
        return null === $value ? new static([]) : new static(json_decode($value, true));
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value instanceof static ?json_encode($value->toArray()) : json_encode($value);
    }

    public function toArray()
    {
        return $this->getArrayCopy();
    }
}
