<?php

namespace Tests\Models;

class Comment extends Model
{
    protected $casts = [
        'options' => 'json'
    ];

    public function commentable()
    {
        return $this->morphTo(null, 'options->commentable_type', 'options->commentable_id');
    }
}
