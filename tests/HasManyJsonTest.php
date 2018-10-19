<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\Models\Post;
use Tests\Models\Role;
use Tests\Models\User;

class HasManyJsonTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (! method_exists(Capsule::connection()->query(), 'whereJsonContains')) {
            $this->markTestSkipped();
        }
    }

    public function testGet()
    {
        $users = Role::first()->users;

        $this->assertEquals([1], $users->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $roles = Role::with('users')->get();

        $this->assertEquals([1], $roles[0]->users->pluck('id')->all());
        $this->assertEquals([1, 3], $roles[1]->users->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $roles = Role::has('users')->get();

        $this->assertEquals([1, 2, 3], $roles->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $posts = Post::has('recommenders')->get();

        $this->assertEquals([2], $posts->pluck('id')->all());
    }

    public function testSave()
    {
        $user = Role::first()->users()->save(User::find(2));

        $this->assertEquals([1], $user->roles()->pluck('id')->all());

        $user = Role::first()->users()->save(User::find(3));

        $this->assertEquals([1, 2, 3], $user->roles()->pluck('id')->all());
    }
}
