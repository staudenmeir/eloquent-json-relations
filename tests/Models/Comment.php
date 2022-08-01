<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    protected $casts = [
        'options' => 'json',
    ];

    public function child(): HasOne
    {
        return $this->hasOne(self::class, 'options->parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'options->parent_id');
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo(null, 'options->commentable_type', 'options->commentable_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'options->parent_id');
    }
}
