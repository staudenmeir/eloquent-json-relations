<?php

namespace Tests\CompositeKeys;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Collection;
use Tests\Models\Employee;
use Tests\TestCase;

class BelongsToJsonTest extends TestCase
{
    public function testLazyLoading()
    {
        $tasks = Employee::find(121)->tasks;

        $this->assertEquals([101, 103, 105], $tasks->pluck('id')->all());
    }

    public function testLazyLoadingWithObjects()
    {
        $tasks = Employee::find(121)->tasksWithObjects;

        $this->assertEquals([101, 103, 105], $tasks->pluck('id')->all());
        $this->assertEquals(['work_stream' => ['active' => true]], $tasks[0]->pivot->getAttributes());
    }

    public function testEmptyLazyLoading()
    {
        DB::enableQueryLog();

        $tasks = (new Employee())->tasks;

        $this->assertInstanceOf(Collection::class, $tasks);
        $this->assertEmpty(DB::getQueryLog());
    }

    public function testEagerLoading()
    {
        $employees = Employee::with('tasks')->get();

        $this->assertEquals([101, 103, 105], $employees[0]->tasks->pluck('id')->all());
        $this->assertEquals([102, 104], $employees[1]->tasks->pluck('id')->all());
        $this->assertEquals([], $employees[3]->tasks->pluck('id')->all());
    }

    public function testEagerLoadingWithObjects()
    {
        $employees = Employee::with('tasksWithObjects')->get();

        $this->assertEquals([101, 103, 105], $employees[0]->tasksWithObjects->pluck('id')->all());
        $this->assertEquals([102, 104], $employees[1]->tasksWithObjects->pluck('id')->all());
        $this->assertEquals([], $employees[3]->tasksWithObjects->pluck('id')->all());
        $this->assertEquals(['work_stream' => ['active' => true]], $employees[0]->tasksWithObjects[0]->pivot->getAttributes());
    }

    public function testLazyEagerLoading()
    {
        $employees = Employee::all()->load('tasks');

        $this->assertEquals([101, 103, 105], $employees[0]->tasks->pluck('id')->all());
        $this->assertEquals([102, 104], $employees[1]->tasks->pluck('id')->all());
        $this->assertEquals([], $employees[3]->tasks->pluck('id')->all());
    }

    public function testLazyEagerLoadingWithObjects()
    {
        $employees = Employee::all()->load('tasksWithObjects');

        $this->assertEquals([101, 103, 105], $employees[0]->tasksWithObjects->pluck('id')->all());
        $this->assertEquals([102, 104], $employees[1]->tasksWithObjects->pluck('id')->all());
        $this->assertEquals([], $employees[3]->tasksWithObjects->pluck('id')->all());
        $this->assertEquals(['work_stream' => ['active' => true]], $employees[0]->tasksWithObjects[0]->pivot->getAttributes());
    }

    public function testExistenceQuery()
    {
        $employees = Employee::has('tasks')->orderBy('id')->get();

        $this->assertEquals([121, 122, 123], $employees->pluck('id')->all());
    }

    public function testExistenceQueryWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $employees = Employee::has('tasksWithObjects')->orderBy('id')->get();

        $this->assertEquals([121, 122, 123], $employees->pluck('id')->all());
    }
}
