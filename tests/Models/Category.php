<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentJsonRelations\Casts\Uuid;

class Category extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'id' => Uuid::class,
        'options' => 'json',
    ];

    public function subProduct()
    {
        return $this->hasOneThrough(Product::class, self::class, 'options->parent_id', 'options->category_id');
    }

    public function subProducts()
    {
        return $this->hasManyThrough(Product::class, self::class, 'options->parent_id', 'options->category_id');
    }
}
