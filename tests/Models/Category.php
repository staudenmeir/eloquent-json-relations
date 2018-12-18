<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $casts = [
        'options' => 'json'
    ];

    public function subProducts()
    {
        return $this->hasManyThrough(Product::class, self::class, 'options->parent_id', 'options->category_id');
    }
}
