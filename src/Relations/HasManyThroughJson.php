<?php

namespace Staudenmeir\EloquentJsonRelations\Relations;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithDictionary;
use Illuminate\Database\Eloquent\SoftDeletes;

class HasManyThroughJson extends HasManyThrough
{
    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        
        $localValue = $this->farParent[$this->localKey];
        $this->performJoin();

        if (static::$constraints) {
            $this->query->whereJsonContains($this->getQualifiedFirstKeyName(), $localValue);
        }
    }

   
}
