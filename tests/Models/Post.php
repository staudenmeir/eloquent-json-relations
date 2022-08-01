<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;
use Staudenmeir\EloquentJsonRelations\Relations\HasManyJson;

class Post extends Model
{
    protected $casts = [
        'options' => 'json',
    ];

    public function comment(): MorphOne
    {
        return $this->morphOne(Comment::class, 'options->commentable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'options->commentable');
    }

    public function recommendations(): BelongsToJson
    {
        return $this->belongsToJson(self::class, 'options->recommendation_ids');
    }

    public function recommendations2(): BelongsToJson
    {
        return $this->belongsToJson(self::class, 'options->recommendations[]->post_id');
    }

    public function recommenders(): HasManyJson
    {
        return $this->hasManyJson(self::class, 'options->recommendation_ids');
    }

    public function recommenders2(): HasManyJson
    {
        return $this->hasManyJson(self::class, 'options->recommendations[]->post_id');
    }
}
