<?php

namespace Tests\Models;

use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

class Employee extends Model
{
    protected $casts = [
        'work_stream_ids' => 'json',
        'options' => 'json',
    ];

    public function tasks(): BelongsToJson
    {
        return $this->belongsToJson(
            Task::class,
            ['work_stream_ids', 'team_id'],
            ['work_stream_id', 'team_id']
        );
    }

    public function tasksWithObjects(): BelongsToJson
    {
        return $this->belongsToJson(
            Task::class,
            ['options->work_streams[]->work_stream->id', 'team_id'],
            ['work_stream_id', 'team_id']
        );
    }
}
