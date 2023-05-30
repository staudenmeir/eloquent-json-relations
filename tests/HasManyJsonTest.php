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

class HasManyJsonTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped();
        }
    }

    #[DataProvider(methodName: 'idRelationProvider')]
    public function testLazyLoading(string $relation)
    {
        $users = Role::find(1)->$relation;

        $this->assertEquals([21], $users->pluck('id')->all());
    }

    #[DataProvider(methodName: 'objectRelationProvider')]
    public function testLazyLoadingWithObjects(string $relation)
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $users = Role::find(1)->$relation;

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

        $user = Role::find(1)->usersWithObjects()->first();

        $this->assertEquals(21, $user->id);
        $this->assertInstanceOf(Pivot::class, $user->pivot);
    }

    #[DataProvider(methodName: 'idRelationProvider')]
    public function testEagerLoading(string $relation)
    {
        $roles = Role::with($relation)->get();

        $this->assertEquals([21], $roles[0]->$relation->pluck('id')->all());
        $this->assertEquals([21, 23], $roles[1]->$relation->pluck('id')->all());
    }

    #[DataProvider(methodName: 'objectRelationProvider')]
    public function testEagerLoadingWithObjects(string $relation)
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $roles = Role::with($relation)->get();

        $this->assertEquals([21], $roles[0]->$relation->pluck('id')->all());
        $this->assertEquals([21, 23], $roles[1]->$relation->pluck('id')->all());
        $pivot = $roles[0]->$relation[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
        $this->assertEquals(['role' => ['active' => false]], $roles[1]->$relation[0]->pivot->getAttributes());
    }

    #[DataProvider(methodName: 'idRelationProvider')]
    public function testLazyEagerLoading(string $relation)
    {
        $roles = Role::all()->load($relation);

        $this->assertEquals([21], $roles[0]->$relation->pluck('id')->all());
        $this->assertEquals([21, 23], $roles[1]->$relation->pluck('id')->all());
    }

    #[DataProvider(methodName: 'objectRelationProvider')]
    public function testLazyEagerLoadingWithObjects(string $relation)
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $roles = Role::all()->load($relation);

        $this->assertEquals([21], $roles[0]->$relation->pluck('id')->all());
        $this->assertEquals([21, 23], $roles[1]->$relation->pluck('id')->all());
        $pivot = $roles[0]->$relation[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
        $this->assertEquals(['role' => ['active' => false]], $roles[1]->$relation[0]->pivot->getAttributes());
    }

    #[DataProvider(methodName: 'idRelationProvider')]
    public function testExistenceQuery(string $relation)
    {
        $roles = Role::has($relation)->get();

        $this->assertEquals([1, 2, 3], $roles->pluck('id')->all());
    }

    #[DataProvider(methodName: 'objectRelationProvider')]
    public function testExistenceQueryWithObjects(string $relation)
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped();
        }

        $roles = Role::whereHas($relation, function (Builder $query) {
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
        $user = Role::find(1)->usersWithObjects()->save(User::find(22));

        $this->assertEquals([1], $user->rolesWithObjects()->pluck('id')->all());

        $user = Role::find(1)->usersWithObjects()->save(User::find(23));

        $this->assertEquals([1, 2, 3], $user->rolesWithObjects()->pluck('id')->all());
    }

    public static function idRelationProvider(): array
    {
        return [
            ['users'],
            ['usersInColumn'],
        ];
    }

    public static function objectRelationProvider(): array
    {
        return [
            ['usersWithObjects'],
            ['usersWithObjectsInColumn'],
        ];
    }
}
