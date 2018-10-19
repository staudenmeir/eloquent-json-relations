<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\Models\Post;
use Tests\Models\User;

class BelongsToJsonTest extends TestCase
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
        $roles = User::first()->roles;

        $this->assertEquals([1, 2], $roles->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $users = User::with('roles')->get();

        $this->assertEquals([1, 2], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([], $users[1]->roles->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->roles->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $users = User::has('roles')->get();

        $this->assertEquals([1, 3], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $posts = Post::has('recommendations')->get();

        $this->assertEquals([1], $posts->pluck('id')->all());
    }

    public function testAttach()
    {
        $user = (new User)->roles()->attach([1, 2]);

        $this->assertEquals([1, 2], $user->roles()->pluck('id')->all());

        $user->roles()->attach([2, 3]);

        $this->assertEquals([1, 2, 3], $user->roles()->pluck('id')->all());
    }

    public function testDetach()
    {
        $user = User::first()->roles()->detach(2);

        $this->assertEquals([1], $user->roles()->pluck('id')->all());

        $user->roles()->detach();

        $this->assertEquals([], $user->roles()->pluck('id')->all());
    }

    public function testSync()
    {
        $user = User::first()->roles()->sync([2, 3]);

        $this->assertEquals([2, 3], $user->roles()->pluck('id')->all());
    }

    public function testToggle()
    {
        $user = User::first()->roles()->toggle([2, 3]);

        $this->assertEquals([1, 3], $user->roles()->pluck('id')->all());
    }
}
