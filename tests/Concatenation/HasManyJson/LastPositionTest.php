<?php

namespace Tests\Concatenation\HasManyJson;

use Illuminate\Database\Capsule\Manager as DB;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Tests\Models\Permission;
use Tests\TestCase;

class LastPositionTest extends TestCase
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
        $users = Permission::find(83)->users;

        $this->assertEquals([21, 23], $users->pluck('id')->all());
    }

    public function testLazyLoadingWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $users = Permission::find(83)->users2;

        $this->assertEquals([21, 23], $users->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $permissions = Permission::with([
            'users' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ])->get();

        $this->assertEquals([21], $permissions[0]->users->pluck('id')->all());
        $this->assertEquals([21, 23], $permissions[2]->users->pluck('id')->all());
        $this->assertEquals([], $permissions[4]->users->pluck('id')->all());
    }

    public function testEagerLoadingWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $permissions = Permission::with('users2')->get();

        $this->assertEquals([21], $permissions[0]->users->pluck('id')->all());
        $this->assertEquals([21, 23], $permissions[2]->users->pluck('id')->all());
        $this->assertEquals([], $permissions[4]->users->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $permissions = Permission::has('users')->get();

        $this->assertEquals([81, 82, 83, 84], $permissions->pluck('id')->all());
    }

    public function testExistenceQueryWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $permissions = Permission::has('users2')->get();

        $this->assertEquals([81, 82, 83, 84], $permissions->pluck('id')->all());
    }
}
