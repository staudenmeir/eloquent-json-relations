<?php

namespace Tests\Concatenation\HasManyJson;

use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Tests\Models\Permission;
use Tests\TestCase;

class IntermediatePositionTest extends TestCase
{
    public function testLazyLoading()
    {
        $countries = Permission::find(83)->countries;

        $this->assertEquals([71, 73], $countries->pluck('id')->all());
    }

    public function testLazyLoadingWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $countries = Permission::find(83)->countries2;

        $this->assertEquals([71, 73], $countries->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $permissions = Permission::with([
            'countries' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ])->get();

        $this->assertEquals([71], $permissions[0]->countries->pluck('id')->all());
        $this->assertEquals([71, 73], $permissions[2]->countries->pluck('id')->all());
        $this->assertEquals([], $permissions[4]->countries->pluck('id')->all());
    }

    public function testEagerLoadingWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $permissions = Permission::with('countries2')->get();

        $this->assertEquals([71], $permissions[0]->countries->pluck('id')->all());
        $this->assertEquals([71, 73], $permissions[2]->countries->pluck('id')->all());
        $this->assertEquals([], $permissions[4]->countries->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $permissions = Permission::has('countries')->get();

        $this->assertEquals([81, 82, 83, 84], $permissions->pluck('id')->all());
    }

    public function testExistenceQueryWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $permissions = Permission::has('countries2')->get();

        $this->assertEquals([81, 82, 83, 84], $permissions->pluck('id')->all());
    }
}
