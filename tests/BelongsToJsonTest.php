<?php

namespace Tests;

use Composer\InstalledVersions;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Tests\Models\Post;
use Tests\Models\Role;
use Tests\Models\User;
use Tests\Models\UserAsArrayable;
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

    // TODO: data provider?

    public function testLazyLoading()
    {
        DB::enableQueryLog();

        $roles = User::find(21)->roles;

        print_r(DB::getQueryLog());
        //exit;

        $this->assertEquals([1, 2], $roles->pluck('id')->all());
    }

    public function testLazyLoadingWithObjects()
    {
        $roles = User::find(21)->roles2;

        $this->assertEquals([1, 2], $roles->pluck('id')->all());
        $pivot = $roles[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
        $this->assertEquals(['role' => ['active' => false]], $roles[1]->pivot->getAttributes());
    }

    public function testLazyLoadingX() // TODO
    {
        DB::enableQueryLog();

        $roles = User::find(21)->roles4;

        print_r(DB::getQueryLog());
        //exit;

        $this->assertEquals([1, 2], $roles->pluck('id')->all());
    }

    public function testLazyLoadingXX() // TODO
    {
        DB::enableQueryLog();

        $roles = User::find(21)->roles5;

        print_r(DB::getQueryLog());

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
        $role = User::find(21)->roles2()->first();

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

    public function testEagerLoadingX() // TODO
    {
        DB::enableQueryLog();

        $users = User::with('roles4')->get();

        print_r(DB::getQueryLog());

        $this->assertEquals([1, 2], $users[0]->roles4->pluck('id')->all());
        $this->assertEquals([], $users[1]->roles4->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->roles4->pluck('id')->all());
    }

    public function testEagerLoadingXX() // TODO
    {
        DB::enableQueryLog();

        $users = User::with('roles5')->get();

        print_r(DB::getQueryLog());

        $this->assertEquals([1, 2], $users[0]->roles5->pluck('id')->all());
        $this->assertEquals([], $users[1]->roles5->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->roles5->pluck('id')->all());
        $pivot = $users[0]->roles5[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
        $this->assertEquals(['role' => ['active' => false]], $users[0]->roles5[1]->pivot->getAttributes());
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
        $users = User::all()->load('roles2');

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
        DB::enableQueryLog();

        $users = User::has('roles')->get();

        print_r(DB::getQueryLog());

        $this->assertEquals([21, 23], $users->pluck('id')->all());
    }

    public function testExistenceQueryWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $users = User::whereHas('roles2', function (Builder $query) {
            $query->where('id', 1);
        })->get();

        $this->assertEquals([21], $users->pluck('id')->all());
    }

    public function testExistenceQueryX() // TODO
    {
        DB::enableQueryLog();

        $users = User::withCount('roles4')->get();
        //$users = User::has('roles4')->get();

        print_r(DB::getQueryLog());

        $this->assertEquals([21, 23], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $posts = Post::has('recommendations')->get();

        $this->assertEquals([31], $posts->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationWithObjects()
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $posts = Post::has('recommendations2')->get();

        $this->assertEquals([31], $posts->pluck('id')->all());
    }

    /**
     * @dataProvider userModelProvider
     */
    public function testAttach(string $userModel)
    {
        // TODO[L10]
        if (version_compare(InstalledVersions::getVersion('illuminate/database'), '9.18') === -1) {
            $this->markTestSkipped();
        }

        $user = (new $userModel())->roles()->attach([1, 2]);

        $this->assertEquals([1, 2], $user->roles()->pluck('id')->all());

        $user->roles()->attach(collect([2, 3]));

        $this->assertEquals([1, 2, 3], $user->roles()->pluck('id')->all());
    }

    /**
     * @dataProvider userModelProvider
     */
    public function testAttachWithObjects(string $userModel)
    {
        // TODO[L10]
        if (version_compare(InstalledVersions::getVersion('illuminate/database'), '9.18') === -1) {
            $this->markTestSkipped();
        }

        $user = new $userModel();
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

    /**
     * @dataProvider userModelProvider
     */
    public function testAttachWithObjectsInColumn(string $userModel)
    {
        $user = (new $userModel())->roles3()->attach([1 => ['active' => true], 2 => ['active' => false]]);

        $this->assertEquals([1, 2], $user->roles3->pluck('id')->all());
        $this->assertEquals([true, false], $user->roles3->pluck('pivot.active')->all());
    }

    public function testDetach()
    {
        $user = User::find(21)->roles()->detach(2);

        $this->assertEquals([1], $user->roles()->pluck('id')->all());

        $user->roles()->detach();

        $this->assertEquals([], $user->roles()->pluck('id')->all());
    }

    public function testDetachWithObjects()
    {
        $user = User::find(21)->roles2()->detach(Role::find(2));

        $this->assertEquals([1], $user->roles2->pluck('id')->all());
        $this->assertEquals([true], $user->roles2->pluck('pivot.role.active')->all());
        $this->assertEquals(['foo' => 'bar'], $user->options['roles'][1]);

        $user->roles2()->detach();

        $this->assertEquals([], $user->roles2()->pluck('id')->all());
        $this->assertEquals(['foo' => 'bar'], $user->options['roles'][0]);
    }

    public function testSync()
    {
        $user = User::find(21)->roles()->sync(Role::find([2, 3]));

        $this->assertEquals([2, 3], $user->roles()->pluck('id')->all());
    }

    public function testSyncWithObjects()
    {
        $user = User::find(21)->roles2()->sync([
            2 => ['role' => ['active' => true]],
            3 => ['role' => ['active' => false]],
        ]);

        $this->assertEquals([2, 3], $user->roles2->pluck('id')->all());
        $this->assertEquals([true, false], $user->roles2->pluck('pivot.role.active')->all());
        $this->assertEquals(['foo' => 'bar'], $user->options['roles'][2]);
    }

    public function testToggle()
    {
        $user = User::find(21)->roles()->toggle([2, 3]);

        $this->assertEquals([1, 3], $user->roles()->pluck('id')->all());
    }

    public function testToggleWithObjects()
    {
        $user = User::find(21)->roles2()->toggle([
            2,
            3 => ['role' => ['active' => false]],
        ]);

        $this->assertEquals([1, 3], $user->roles2->pluck('id')->all());
        $this->assertEquals([true, false], $user->roles2->pluck('pivot.role.active')->all());
        $this->assertEquals(['foo' => 'bar'], $user->options['roles'][2]);
    }

    /**
     * @dataProvider userModelProvider
     */
    public function testForeignKeys(string $userModel)
    {
        $keys = $userModel::find(21)->roles()->getForeignKeys();

        $this->assertEquals([1, 2], $keys);
    }

    public function userModelProvider(): array
    {
        $users = [
            [User::class],
            [UserAsArrayable::class],
        ];

        // TODO[L10]
        if (class_exists('Illuminate\Database\Eloquent\Casts\AsArrayObject')) {
            $users[] = [UserAsArrayObject::class];
            $users[] = [UserAsCollection::class];
        }

        return $users;
    }
}
