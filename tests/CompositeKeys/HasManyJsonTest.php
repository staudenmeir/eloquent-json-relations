<?php

namespace Tests\CompositeKeys;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Collection;
use Tests\Models\Task;
use Tests\TestCase;

class HasManyJsonTest extends TestCase
{
    public function testLazyLoading()
    {
        $employees = Task::find(101)->employees;

        $this->assertEquals([121, 123], $employees->pluck('id')->all());
    }

    public function testLazyLoadingWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $employees = Task::find(101)->employeesWithObjects;

        $this->assertEquals([121, 123], $employees->pluck('id')->all());
        $this->assertEquals(['work_stream' => ['active' => true]], $employees[0]->pivot->getAttributes());
    }

    public function testEmptyLazyLoading()
    {
        DB::enableQueryLog();

        $employees = (new Task())->employees;

        $this->assertInstanceOf(Collection::class, $employees);
        $this->assertEmpty(DB::getQueryLog());
    }

    public function testEagerLoading()
    {
        $tasks = Task::with('employees')->get();

        $this->assertEquals([121, 123], $tasks[0]->employees->pluck('id')->all());
        $this->assertEquals([122], $tasks[1]->employees->pluck('id')->all());
        $this->assertEquals([], $tasks[5]->employees->pluck('id')->all());
    }

    public function testEagerLoadingWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $tasks = Task::with('employeesWithObjects')->get();

        $this->assertEquals([121, 123], $tasks[0]->employeesWithObjects->pluck('id')->all());
        $this->assertEquals([122], $tasks[1]->employeesWithObjects->pluck('id')->all());
        $this->assertEquals([], $tasks[5]->employeesWithObjects->pluck('id')->all());
        $this->assertEquals(['work_stream' => ['active' => true]], $tasks[0]->employeesWithObjects[0]->pivot->getAttributes());
    }

    public function testLazyEagerLoading()
    {
        $tasks = Task::all()->load('employees');

        $this->assertEquals([121, 123], $tasks[0]->employees->pluck('id')->all());
        $this->assertEquals([122], $tasks[1]->employees->pluck('id')->all());
        $this->assertEquals([], $tasks[5]->employees->pluck('id')->all());
    }

    public function testLazyEagerLoadingWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $tasks = Task::all()->load('employeesWithObjects');

        $this->assertEquals([121, 123], $tasks[0]->employeesWithObjects->pluck('id')->all());
        $this->assertEquals([122], $tasks[1]->employeesWithObjects->pluck('id')->all());
        $this->assertEquals([], $tasks[5]->employeesWithObjects->pluck('id')->all());
        $this->assertEquals(['work_stream' => ['active' => true]], $tasks[0]->employeesWithObjects[0]->pivot->getAttributes());
    }

    public function testExistenceQuery()
    {
        $tasks = Task::has('employees')->orderBy('id')->get();

        $this->assertEquals([101, 102, 103, 104, 105], $tasks->pluck('id')->all());
    }

    public function testExistenceQueryWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $tasks = Task::has('employeesWithObjects')->orderBy('id')->get();

        $this->assertEquals([101, 102, 103, 104, 105], $tasks->pluck('id')->all());
    }
}
