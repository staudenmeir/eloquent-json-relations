<?php

namespace Tests\Concatenation\HasManyJson;

use Tests\Models\Role;
use Tests\TestCase;

class FirstPositionTest extends TestCase
{
    public function testLazyLoading()
    {
        $countries = Role::find(2)->countries;

        $this->assertEquals([71, 73], $countries->pluck('id')->all());
    }

    public function testLazyLoadingWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $countries = Role::find(2)->countries2;

        $this->assertEquals([71, 73], $countries->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $roles = Role::with('countries')->get();

        $this->assertEquals([71], $roles[0]->countries->pluck('id')->all());
        $this->assertEquals([71, 73], $roles[1]->countries->pluck('id')->all());
        $this->assertEquals([], $roles[3]->countries->pluck('id')->all());
    }

    public function testEagerLoadingWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $roles = Role::with('countries2')->get();

        $this->assertEquals([71], $roles[0]->countries2->pluck('id')->all());
        $this->assertEquals([71, 73], $roles[1]->countries2->pluck('id')->all());
        $this->assertEquals([], $roles[3]->countries2->pluck('id')->all());
    }

    public function testEagerLoadingWithHasOneDeep()
    {
        $roles = Role::with('country')->get();

        $this->assertEquals(71, $roles[0]->country->id);
        $this->assertEquals(71, $roles[1]->country->id);
        $this->assertNull($roles[3]->country);
    }

    public function testLazyEagerLoading()
    {
        $roles = Role::all()->load('countries');

        $this->assertEquals([71], $roles[0]->countries->pluck('id')->all());
        $this->assertEquals([71, 73], $roles[1]->countries->pluck('id')->all());
        $this->assertEquals([], $roles[3]->countries->pluck('id')->all());
    }

    public function testLazyEagerLoadingWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $roles = Role::all()->load('countries2');

        $this->assertEquals([71], $roles[0]->countries2->pluck('id')->all());
        $this->assertEquals([71, 73], $roles[1]->countries2->pluck('id')->all());
        $this->assertEquals([], $roles[3]->countries2->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $roles = Role::has('countries')->get();

        $this->assertEquals([1, 2, 3], $roles->pluck('id')->all());
    }

    public function testExistenceQueryWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $roles = Role::has('countries2')->get();

        $this->assertEquals([1, 2, 3], $roles->pluck('id')->all());
    }
}
