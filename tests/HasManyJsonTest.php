<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Tests\Models\Post;
use Tests\Models\Role;
use Tests\Models\User;

class HasManyJsonTest extends TestCase
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
        $users = Role::find(1)->users;

        $this->assertEquals([21], $users->pluck('id')->all());
    }

    public function testLazyLoadingWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $users = Role::find(1)->users2;

        $this->assertEquals([21], $users->pluck('id')->all());
        $pivot = $users[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
    }

    public function testEmptyLazyLoading()
    {
        DB::enableQueryLog();

        $roles = (new Role())->users;

        $this->assertInstanceOf(Collection::class, $roles);
        $this->assertEmpty(DB::getQueryLog());
    }

    public function testFirst()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $user = Role::find(1)->users2()->first();

        $this->assertEquals(21, $user->id);
        $this->assertInstanceOf(Pivot::class, $user->pivot);
    }

    public function testEagerLoading()
    {
        $roles = Role::with('users')->get();

        $this->assertEquals([21], $roles[0]->users->pluck('id')->all());
        $this->assertEquals([21, 23], $roles[1]->users->pluck('id')->all());
    }

    public function testEagerLoadingWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $roles = Role::with('users2')->get();

        $this->assertEquals([21], $roles[0]->users2->pluck('id')->all());
        $this->assertEquals([21, 23], $roles[1]->users2->pluck('id')->all());
        $pivot = $roles[0]->users2[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
        $this->assertEquals(['role' => ['active' => false]], $roles[1]->users2[0]->pivot->getAttributes());
    }

    public function testLazyEagerLoading()
    {
        $roles = Role::all()->load('users');

        $this->assertEquals([21], $roles[0]->users->pluck('id')->all());
        $this->assertEquals([21, 23], $roles[1]->users->pluck('id')->all());
    }

    public function testLazyEagerLoadingWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $roles = Role::all()->load('users2');

        $this->assertEquals([21], $roles[0]->users2->pluck('id')->all());
        $this->assertEquals([21, 23], $roles[1]->users2->pluck('id')->all());
        $pivot = $roles[0]->users2[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
        $this->assertEquals(['role' => ['active' => false]], $roles[1]->users2[0]->pivot->getAttributes());
    }

    public function testExistenceQuery()
    {
        $roles = Role::has('users')->get();

        $this->assertEquals([1, 2, 3], $roles->pluck('id')->all());
    }

    public function testExistenceQueryWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $roles = Role::whereHas('users2', function (Builder $query) {
            $query->where('id', 21);
        })->get();

        $this->assertEquals([1, 2], $roles->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $posts = Post::has('recommenders')->get();

        $this->assertEquals([32], $posts->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $posts = Post::has('recommenders2')->get();

        $this->assertEquals([32], $posts->pluck('id')->all());
    }

    public function testSave()
    {
        $user = Role::find(1)->users()->save(User::find(22));

        $this->assertEquals([1], $user->roles()->pluck('id')->all());

        $user = Role::find(1)->users()->save(User::find(23));

        $this->assertEquals([1, 2, 3], $user->roles()->pluck('id')->all());
    }

    public function testSaveWithObjects()
    {
        $user = Role::find(1)->users2()->save(User::find(22));

        $this->assertEquals([1], $user->roles2()->pluck('id')->all());

        $user = Role::find(1)->users2()->save(User::find(23));

        $this->assertEquals([1, 2, 3], $user->roles2()->pluck('id')->all());
    }
}
