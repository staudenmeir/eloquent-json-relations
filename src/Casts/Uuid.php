<?php

namespace Staudenmeir\EloquentJsonRelations\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * @template TGet
 * @template TSet
 *
 * @implements \Illuminate\Contracts\Database\Eloquent\CastsAttributes<TGet, TSet>
 */
class Uuid implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     * @return TGet|null
     */
    public function get($model, $key, $value, $attributes)
    {
        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @param TSet|null $value
     * @param array<string, mixed> $attributes
     * @return mixed
     */
    public function set($model, $key, $value, $attributes)
    {
        return $value;
    }
}
