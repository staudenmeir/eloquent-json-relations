<?php

namespace Tests\Concatenation\BelongsToJson;

use Illuminate\Database\Capsule\Manager as DB;
use Tests\Models\User;
use Tests\TestCase;

class FirstPositionTest extends TestCase
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
        $permissions = User::find(21)->permissions;

        $this->assertEquals([81, 82, 83], $permissions->pluck('id')->all());
    }

    public function testLazyLoadingWithObjects()
    {
        $permissions = User::find(21)->permissions2;

        $this->assertEquals([81, 82, 83], $permissions->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $users = User::with('permissions')->get();

        $this->assertEquals([81, 82, 83], $users[0]->permissions->pluck('id')->all());
        $this->assertEquals([], $users[1]->permissions->pluck('id')->all());
        $this->assertEquals([83, 84], $users[2]->permissions->pluck('id')->all());
    }

    public function testEagerLoadingWithObjects()
    {
        $users = User::with('permissions2')->get();

        $this->assertEquals([81, 82, 83], $users[0]->permissions2->pluck('id')->all());
        $this->assertEquals([], $users[1]->permissions2->pluck('id')->all());
        $this->assertEquals([83, 84], $users[2]->permissions2->pluck('id')->all());
    }

    public function testLazyEagerLoading()
    {
        $users = User::all()->load('permissions');

        $this->assertEquals([81, 82, 83], $users[0]->permissions->pluck('id')->all());
        $this->assertEquals([], $users[1]->permissions->pluck('id')->all());
        $this->assertEquals([83, 84], $users[2]->permissions->pluck('id')->all());
    }

    public function testLazyEagerLoadingWithObjects()
    {
        $users = User::all()->load('permissions2');

        $this->assertEquals([81, 82, 83], $users[0]->permissions2->pluck('id')->all());
        $this->assertEquals([], $users[1]->permissions2->pluck('id')->all());
        $this->assertEquals([83, 84], $users[2]->permissions2->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $users = User::has('permissions')->get();

        $this->assertEquals([21, 23], $users->pluck('id')->all());
    }

    public function testExistenceQueryWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $users = User::has('permissions2')->get();

        $this->assertEquals([21, 23], $users->pluck('id')->all());
    }
}
