<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Tests\Models\Post;
use Tests\Models\Role;
use Tests\Models\User;
use Tests\Models\UserAsArrayObject;
use Tests\Models\UserAsCollection;

class BelongsToJsonTest extends TestCase
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
        $roles = User::first()->roles;

        $this->assertEquals([1, 2], $roles->pluck('id')->all());
    }

    public function testLazyLoadingWithObjects()
    {
        $roles = User::first()->roles2;

        $this->assertEquals([1, 2], $roles->pluck('id')->all());
        $pivot = $roles[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
        $this->assertEquals(['role' => ['active' => false]], $roles[1]->pivot->getAttributes());
    }

    public function testEmptyLazyLoading()
    {
        DB::enableQueryLog();

        $roles = (new User())->roles;

        $this->assertInstanceOf(Collection::class, $roles);
        $this->assertEmpty(DB::getQueryLog());
    }

    public function testFirst()
    {
        $role = User::first()->roles2()->first();

        $this->assertEquals(1, $role->id);
        $this->assertInstanceOf(Pivot::class, $role->pivot);
    }

    public function testEagerLoading()
    {
        $users = User::with('roles')->get();

        $this->assertEquals([1, 2], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([], $users[1]->roles->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->roles->pluck('id')->all());
    }

    public function testEagerLoadingWithObjects()
    {
        $users = User::with('roles2')->get();

        $this->assertEquals([1, 2], $users[0]->roles2->pluck('id')->all());
        $this->assertEquals([], $users[1]->roles2->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->roles2->pluck('id')->all());
        $pivot = $users[0]->roles2[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
        $this->assertEquals(['role' => ['active' => false]], $users[0]->roles2[1]->pivot->getAttributes());
    }

    public function testLazyEagerLoading()
    {
        $users = User::all()->load('roles');

        $this->assertEquals([1, 2], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([], $users[1]->roles->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->roles->pluck('id')->all());
    }

    public function testLazyEagerLoadingWithObjects()
    {
        $users = User::get()->load('roles2');

        $this->assertEquals([1, 2], $users[0]->roles2->pluck('id')->all());
        $this->assertEquals([], $users[1]->roles2->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->roles2->pluck('id')->all());
        $pivot = $users[0]->roles2[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
        $this->assertEquals(['role' => ['active' => false]], $users[0]->roles2[1]->pivot->getAttributes());
    }

    public function testExistenceQuery()
    {
        $users = User::has('roles')->get();

        $this->assertEquals([1, 3], $users->pluck('id')->all());
    }

    public function testExistenceQueryWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $users = User::whereHas('roles2', function (Builder $query) {
            $query->where('id', 1);
        })->get();

        $this->assertEquals([1], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $posts = Post::has('recommendations')->get();

        $this->assertEquals([1], $posts->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $posts = Post::has('recommendations2')->get();

        $this->assertEquals([1], $posts->pluck('id')->all());
    }

    public function testAttach()
    {
        $user = (new User())->roles()->attach([1, 2]);

        $this->assertEquals([1, 2], $user->roles()->pluck('id')->all());

        $user->roles()->attach(collect([2, 3]));

        $this->assertEquals([1, 2, 3], $user->roles()->pluck('id')->all());
    }

    public function testAttachWithObjects()
    {
        $user = new User();
        $user->options = [
            'roles' => [
                ['foo' => 'bar'],
            ],
        ];

        $user->roles2()->attach([
            1 => ['role' => ['active' => true]],
            2 => ['role' => ['active' => false]],
        ]);

        $this->assertEquals([1, 2], $user->roles2->pluck('id')->all());
        $this->assertEquals([true, false], $user->roles2->pluck('pivot.role.active')->all());

        $user->roles2()->attach([
            2 => ['role' => ['active' => true]],
            3 => ['role' => ['active' => false]],
        ]);

        $roles = $user->load('roles2')->roles2->sortBy('id')->values();
        $this->assertEquals([1, 2, 3], $roles->pluck('id')->all());
        $this->assertEquals([true, true, false], $roles->pluck('pivot.role.active')->all());
        $this->assertEquals(['foo' => 'bar'], $user->options['roles'][3]);
    }

    public function testAttachWithObjectsInColumn()
    {
        $user = (new User())->roles3()->attach([1 => ['active' => true], 2 => ['active' => false]]);

        $this->assertEquals([1, 2], $user->roles3->pluck('id')->all());
        $this->assertEquals([true, false], $user->roles3->pluck('pivot.active')->all());
    }

    public function testDetach()
    {
        $user = User::first()->roles()->detach(2);

        $this->assertEquals([1], $user->roles()->pluck('id')->all());

        $user->roles()->detach();

        $this->assertEquals([], $user->roles()->pluck('id')->all());
    }

    public function testDetachWithObjects()
    {
        $user = User::first()->roles2()->detach(Role::find(2));

        $this->assertEquals([1], $user->roles2->pluck('id')->all());
        $this->assertEquals([true], $user->roles2->pluck('pivot.role.active')->all());
        $this->assertEquals(['foo' => 'bar'], $user->options['roles'][1]);

        $user->roles2()->detach();

        $this->assertEquals([], $user->roles2()->pluck('id')->all());
        $this->assertEquals(['foo' => 'bar'], $user->options['roles'][0]);
    }

    public function testSync()
    {
        $user = User::first()->roles()->sync(Role::find([2, 3]));

        $this->assertEquals([2, 3], $user->roles()->pluck('id')->all());
    }

    public function testSyncWithObjects()
    {
        $user = User::first()->roles2()->sync([
            2 => ['role' => ['active' => true]],
            3 => ['role' => ['active' => false]],
        ]);

        $this->assertEquals([2, 3], $user->roles2->pluck('id')->all());
        $this->assertEquals([true, false], $user->roles2->pluck('pivot.role.active')->all());
        $this->assertEquals(['foo' => 'bar'], $user->options['roles'][2]);
    }

    public function testToggle()
    {
        $user = User::first()->roles()->toggle([2, 3]);

        $this->assertEquals([1, 3], $user->roles()->pluck('id')->all());
    }

    public function testToggleWithObjects()
    {
        $user = User::first()->roles2()->toggle([
            2,
            3 => ['role' => ['active' => false]],
        ]);

        $this->assertEquals([1, 3], $user->roles2->pluck('id')->all());
        $this->assertEquals([true, false], $user->roles2->pluck('pivot.role.active')->all());
        $this->assertEquals(['foo' => 'bar'], $user->options['roles'][2]);
    }

    /**
     * @dataProvider foreignKeysDataProvider
     */
    public function testForeignKeys($user)
    {
        $keys = $user::first()->roles()->getForeignKeys();

        $this->assertEquals([1, 2], $keys);
    }

    public function foreignKeysDataProvider()
    {
        $users = [
            [User::class],
        ];

        if (class_exists('Illuminate\Database\Eloquent\Casts\AsArrayObject')) {
            $users[] = [UserAsArrayObject::class];
            $users[] = [UserAsCollection::class];
        }

        return $users;
    }
}
