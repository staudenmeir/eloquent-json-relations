<?php

namespace Tests\Concatenation\BelongsToJson;

use Illuminate\Database\Capsule\Manager as DB;
use Tests\Models\Country;
use Tests\TestCase;

class IntermediatePositionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped();
        }
    }

    public function testLazyLoading()
    {
        $permissions = Country::find(71)->permissions;

        $this->assertEquals([81, 82, 83], $permissions->pluck('id')->all());
    }

    public function testLazyLoadingWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $permissions = Country::find(71)->permissions2;

        $this->assertEquals([81, 82, 83], $permissions->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $countries = Country::with('permissions')->get();

        $this->assertEquals([81, 82, 83], $countries[0]->permissions->pluck('id')->all());
        $this->assertEquals([], $countries[1]->permissions->pluck('id')->all());
        $this->assertEquals([83, 84], $countries[2]->permissions->pluck('id')->all());
    }

    public function testEagerLoadingWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $countries = Country::with('permissions2')->get();

        $this->assertEquals([81, 82, 83], $countries[0]->permissions2->pluck('id')->all());
        $this->assertEquals([], $countries[1]->permissions2->pluck('id')->all());
        $this->assertEquals([83, 84], $countries[2]->permissions2->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $countries = Country::has('permissions')->get();

        $this->assertEquals([71, 73], $countries->pluck('id')->all());
    }

    public function testExistenceQueryWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $countries = Country::has('permissions2')->get();

        $this->assertEquals([71, 73], $countries->pluck('id')->all());
    }
}
