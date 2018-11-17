<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Tests\Models\Post;
use Tests\Models\Role;
use Tests\Models\User;

class HasManyJsonTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (! method_exists(DB::connection()->query(), 'whereJsonContains')) {
            $this->markTestSkipped();
        }
    }

    public function testLazyLoading()
    {
        $users = Role::first()->users;

        $this->assertEquals([1], $users->pluck('id')->all());
    }

    public function testLazyLoadingWithObjects()
    {
        $users = Role::first()->users2;

        $this->assertEquals([1], $users->pluck('id')->all());
        $pivot = $users[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['active' => true], $pivot->getAttributes());
    }

    public function testEagerLoading()
    {
        $roles = Role::with('users')->get();

        $this->assertEquals([1], $roles[0]->users->pluck('id')->all());
        $this->assertEquals([1, 3], $roles[1]->users->pluck('id')->all());
    }

    public function testEagerLoadingWithObjects()
    {
        $roles = Role::with('users2')->get();

        $this->assertEquals([1], $roles[0]->users2->pluck('id')->all());
        $this->assertEquals([1, 3], $roles[1]->users2->pluck('id')->all());
        $pivot = $roles[0]->users2[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['active' => true], $pivot->getAttributes());
    }

    public function testLazyEagerLoading()
    {
        $roles = Role::all()->load('users');

        $this->assertEquals([1], $roles[0]->users->pluck('id')->all());
        $this->assertEquals([1, 3], $roles[1]->users->pluck('id')->all());
    }

    public function testLazyEagerLoadingWithObjects()
    {
        $roles = Role::all()->load('users2');

        $this->assertEquals([1], $roles[0]->users2->pluck('id')->all());
        $this->assertEquals([1, 3], $roles[1]->users2->pluck('id')->all());
        $pivot = $roles[0]->users2[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['active' => true], $pivot->getAttributes());
    }

    public function testExistenceQuery()
    {
        $roles = Role::has('users')->get();

        $this->assertEquals([1, 2, 3], $roles->pluck('id')->all());
    }

    public function testExistenceQueryWithObjects()
    {
        $roles = Role::has('users2')->get();

        $this->assertEquals([1, 2, 3], $roles->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $posts = Post::has('recommenders')->get();

        $this->assertEquals([2], $posts->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationWithObjects()
    {
        $posts = Post::has('recommenders2')->get();

        $this->assertEquals([2], $posts->pluck('id')->all());
    }

    public function testSave()
    {
        $user = Role::first()->users()->save(User::find(2));

        $this->assertEquals([1], $user->roles()->pluck('id')->all());

        $user = Role::first()->users()->save(User::find(3));

        $this->assertEquals([1, 2, 3], $user->roles()->pluck('id')->all());
    }

    public function testSaveWithObjects()
    {
        $user = Role::first()->users2()->save(User::find(2));

        $this->assertEquals([1], $user->roles2()->pluck('id')->all());

        $user = Role::first()->users2()->save(User::find(3));

        $this->assertEquals([1, 2, 3], $user->roles2()->pluck('id')->all());
    }
}
