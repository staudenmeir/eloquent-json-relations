<?php

namespace Tests\Models;

use Staudenmeir\EloquentJsonRelations\Relations\HasManyJson;
use Staudenmeir\EloquentJsonRelations\Relations\HasOneJson;

class Task extends Model
{
    public function employee(): HasOneJson
    {
        return $this->hasOneJson(
            Employee::class,
            ['work_stream_ids', 'team_id'],
            ['work_stream_id', 'team_id']
        );
    }

    public function employees(): HasManyJson
    {
        return $this->hasManyJson(
            Employee::class,
            ['work_stream_ids', 'team_id'],
            ['work_stream_id', 'team_id']
        );
    }

    public function employeeWithObjects(): HasOneJson
    {
        return $this->hasOneJson(
            Employee::class,
            ['options->work_streams[]->work_stream->id', 'team_id'],
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
