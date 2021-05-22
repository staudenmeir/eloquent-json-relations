<?php

namespace Tests\Models;

class Comment extends Model
{
    protected $casts = [
        'options' => 'json',
    ];

    public function child()
    {
        return $this->hasOne(self::class, 'options->parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'options->parent_id');
    }

    public function commentable()
    {
        return $this->morphTo(null, 'options->commentable_type', 'options->commentable_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'options->parent_id');
    }
}
