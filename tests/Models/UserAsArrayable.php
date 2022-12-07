<?php

namespace Tests\Models;

use ArrayIterator;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

class UserAsArrayable extends User
{
    protected $table = 'users';

    protected $casts = [
        'options' => ArrayableCast::class,
    ];

    public function roles(): BelongsToJson
    {
        return $this->belongsToJson(Role::class, 'options->role_ids');
    }

    public function roles3(): BelongsToJson
    {
        return $this->belongsToJson(Role::class, 'options[]->role_id');
    }
}

class ArrayableCast extends ArrayIterator implements CastsAttributes, Arrayable
{
    public function get($model, string $key, $value, array $attributes)
    {
        return null === $value ? new static([]) : new static(json_decode($value, true));
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value instanceof static ? json_encode($value->toArray()) : json_encode($value);
    }

    public function toArray()
    {
        return $this->getArrayCopy();
    }
}