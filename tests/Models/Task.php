<?php

namespace Tests\Models;

use Staudenmeir\EloquentJsonRelations\Relations\HasManyJson;

class Task extends Model
{
    public function employees(): HasManyJson
    {
        return $this->hasManyJson(
            Employee::class,
            ['work_stream_ids', 'team_id'],
            ['work_stream_id', 'team_id']
        );
    }

    public function employeesWithObjects(): HasManyJson
    {
        return $this->hasManyJson(
            Employee::class,
            ['options->work_streams[]->work_stream->id', 'team_id'],
            ['work_stream_id', 'team_id']
        );
    }
}
