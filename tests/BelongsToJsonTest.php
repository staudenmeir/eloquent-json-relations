<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Models\Post;
use Tests\Models\Role;
use Tests\Models\User;
use Tests\Models\UserAsArrayable;
use Tests\Models\UserAsArrayObject;
use Tests\Models\UserAsCollection;

class BelongsToJsonTest extends TestCase
{
    #[DataProvider(methodName: 'idRelationProvider')]
    public function testLazyLoading(string $relation)
    {
        $roles = User::find(21)->$relation;

        $this->assertEquals([1, 2], $roles->pluck('id')->all());
    }

    #[DataProvider(methodName: 'objectRelationProvider')]
    public function testLazyLoadingWithObjects(string $relation)
    {
        $roles = User::find(21)->$relation;

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
        $role = User::find(21)->rolesWithObjects()->first();

        $this->assertEquals(1, $role->id);
        $this->assertInstanceOf(Pivot::class, $role->pivot);
    }

    #[DataProvider(methodName: 'idRelationProvider')]
    public function testEagerLoading(string $relation)
    {
        $users = User::with($relation)->get();

        $this->assertEquals([1, 2], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([], $users[1]->roles->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->roles->pluck('id')->all());
    }

    #[DataProvider(methodName: 'objectRelationProvider')]
    public function testEagerLoadingWithObjects(string $relation)
    {
        $users = User::with($relation)->get();

        $this->assertEquals([1, 2], $users[0]->$relation->pluck('id')->all());
        $this->assertEquals([], $users[1]->$relation->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->$relation->pluck('id')->all());
        $pivot = $users[0]->$relation[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
        $this->assertEquals(['role' => ['active' => false]], $users[0]->$relation[1]->pivot->getAttributes());
    }

    #[DataProvider(methodName: 'idRelationProvider')]
    public function testLazyEagerLoading(string $relation)
    {
        $users = User::all()->load($relation);

        $this->assertEquals([1, 2], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([], $users[1]->roles->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->roles->pluck('id')->all());
    }

    #[DataProvider(methodName: 'objectRelationProvider')]
    public function testLazyEagerLoadingWithObjects(string $relation)
    {
        $users = User::all()->load($relation);

        $this->assertEquals([1, 2], $users[0]->$relation->pluck('id')->all());
        $this->assertEquals([], $users[1]->$relation->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->$relation->pluck('id')->all());
        $pivot = $users[0]->$relation[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
        $this->assertEquals(['role' => ['active' => false]], $users[0]->$relation[1]->pivot->getAttributes());
    }

    #[DataProvider(methodName: 'idRelationProvider')]
    public function testExistenceQuery(string $relation)
    {
        $users = User::has($relation)->get();

        $this->assertEquals([21, 23], $users->pluck('id')->all());
    }

    #[DataProvider(methodName: 'objectRelationProvider')]
    public function testExistenceQueryWithObjects(string $relation)
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $users = User::whereHas($relation, function (Builder $query) {
            $query->where('id', 1);
        })->get();

        $this->assertEquals([21], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $posts = Post::has('recommendations')->get();

        $this->assertEquals([31], $posts->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationWithObjects()
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $posts = Post::has('recommendations2')->get();

        $this->assertEquals([31], $posts->pluck('id')->all());
    }

    #[DataProvider(methodName: 'userModelProvider')]
    public function testAttach(string $userModel)
    {
        $user = (new $userModel())->roles()->attach([1, 2]);

        $this->assertEquals([1, 2], $user->roles()->pluck('id')->all());

        $user->roles()->attach(collect([2, 3]));

        $this->assertEquals([1, 2, 3], $user->roles()->pluck('id')->all());
    }

    #[DataProvider(methodName: 'userModelProvider')]
    public function testAttachWithObjects(string $userModel)
    {
        $user = new $userModel();
        $user->options = [
            'roles' => [
                ['foo' => 'bar'],
            ],
        ];

        $user->rolesWithObjects()->attach([
            1 => ['role' => ['active' => true]],
            2 => ['role' => ['active' => false]],
        ]);

        $this->assertEquals([1, 2], $user->rolesWithObjects->pluck('id')->all());
        $this->assertEquals([true, false], $user->rolesWithObjects->pluck('pivot.role.active')->all());

        $user->rolesWithObjects()->attach([
            2 => ['role' => ['active' => true]],
            3 => ['role' => ['active' => false]],
        ]);

        $roles = $user->load('rolesWithObjects')->rolesWithObjects->sortBy('id')->values();
        $this->assertEquals([1, 2, 3], $roles->pluck('id')->all());
        $this->assertEquals([true, true, false], $roles->pluck('pivot.role.active')->all());
        $this->assertEquals(['foo' => 'bar'], $user->options['roles'][3]);
    }

    #[DataProvider(methodName: 'userModelProvider')]
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
        $user = User::find(21)->rolesWithObjects()->detach(Role::find(2));

        $this->assertEquals([1], $user->rolesWithObjects->pluck('id')->all());
        $this->assertEquals([true], $user->rolesWithObjects->pluck('pivot.role.active')->all());
        $this->assertEquals(['foo' => 'bar'], $user->options['roles'][1]);

        $user->rolesWithObjects()->detach();

        $this->assertEquals([], $user->rolesWithObjects()->pluck('id')->all());
        $this->assertEquals(['foo' => 'bar'], $user->options['roles'][0]);
    }

    public function testSync()
    {
        $user = User::find(21)->roles()->sync(Role::find([2, 3]));

        $this->assertEquals([2, 3], $user->roles()->pluck('id')->all());
    }

    public function testSyncWithObjects()
    {
        $user = User::find(21)->rolesWithObjects()->sync([
            2 => ['role' => ['active' => true]],
            3 => ['role' => ['active' => false]],
        ]);

        $this->assertEquals([2, 3], $user->rolesWithObjects->pluck('id')->all());
        $this->assertEquals([true, false], $user->rolesWithObjects->pluck('pivot.role.active')->all());
        $this->assertEquals(['foo' => 'bar'], $user->options['roles'][2]);
    }

    public function testToggle()
    {
        $user = User::find(21)->roles()->toggle([2, 3]);

        $this->assertEquals([1, 3], $user->roles()->pluck('id')->all());
    }

    public function testToggleWithObjects()
    {
        $user = User::find(21)->rolesWithObjects()->toggle([
            2,
            3 => ['role' => ['active' => false]],
        ]);

        $this->assertEquals([1, 3], $user->rolesWithObjects->pluck('id')->all());
        $this->assertEquals([true, false], $user->rolesWithObjects->pluck('pivot.role.active')->all());
        $this->assertEquals(['foo' => 'bar'], $user->options['roles'][2]);
    }

    #[DataProvider(methodName: 'userModelProvider')]
    public function testForeignKeys(string $userModel)
    {
        $keys = $userModel::find(21)->roles()->getForeignKeys();

        $this->assertEquals([1, 2], $keys);
    }

    public function testGetRelatedKeyName()
    {
        $relatedKeyName = (new User())->roles()->getRelatedKeyName();

        $this->assertEquals('id', $relatedKeyName);
    }

    public static function idRelationProvider(): array
    {
        return [
            ['roles'],
            ['rolesInColumn'],
        ];
    }

    public static function objectRelationProvider(): array
    {
        return [
            ['rolesWithObjects'],
            ['rolesWithObjectsInColumn'],
        ];
    }

    public static function userModelProvider(): array
    {
        return [
            [User::class],
            [UserAsArrayable::class],
            [UserAsArrayObject::class],
            [UserAsCollection::class],
        ];
    }
}
