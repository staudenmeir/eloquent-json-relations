<?php

namespace Tests\CompositeKeys;

use Illuminate\Database\Capsule\Manager as DB;
use Tests\Models\Task;
use Tests\TestCase;

class HasOneJsonTest extends TestCase
{
    public function testLazyLoading()
    {
        $employee = Task::find(101)->employee;

        $this->assertEquals(121, $employee->id);
    }

    public function testLazyLoadingWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $employee = Task::find(101)->employeeWithObjects;

        $this->assertEquals(121, $employee->id);
        $this->assertEquals(['work_stream' => ['active' => true]], $employee->pivot->getAttributes());
    }

    public function testEmptyLazyLoading()
    {
        DB::connection()->enableQueryLog();

        $employee = (new Task())->employee;

        $this->assertNull($employee);
        $this->assertEmpty(DB::connection()->getQueryLog());
    }

    public function testEagerLoading()
    {
        $tasks = Task::with('employee')->get();

        $this->assertEquals(121, $tasks[0]->employee->id);
        $this->assertEquals(122, $tasks[1]->employee->id);
        $this->assertNull($tasks[5]->employee);
    }

    public function testEagerLoadingWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $tasks = Task::with('employeeWithObjects')->get();

        $this->assertEquals(121, $tasks[0]->employeeWithObjects->id);
        $this->assertEquals(122, $tasks[1]->employeeWithObjects->id);
        $this->assertNull($tasks[5]->employeeWithObjects);
        $this->assertEquals(['work_stream' => ['active' => true]], $tasks[0]->employeeWithObjects->pivot->getAttributes());
    }

    public function testLazyEagerLoading()
    {
        $tasks = Task::all()->load('employee');

        $this->assertEquals(121, $tasks[0]->employee->id);
        $this->assertEquals(122, $tasks[1]->employee->id);
        $this->assertNull($tasks[5]->employee);
    }

    public function testLazyEagerLoadingWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $tasks = Task::all()->load('employeeWithObjects');

        $this->assertEquals(121, $tasks[0]->employeeWithObjects->id);
        $this->assertEquals(122, $tasks[1]->employeeWithObjects->id);
        $this->assertNull($tasks[5]->employeeWithObjects);
        $this->assertEquals(['work_stream' => ['active' => true]], $tasks[0]->employeeWithObjects->pivot->getAttributes());
    }
}
