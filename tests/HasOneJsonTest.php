<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Relations\Pivot;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Models\Role;
use Tests\Models\User;

class HasOneJsonTest extends TestCase
{
    #[DataProvider(methodName: 'idRelationProvider')]
    public function testLazyLoading(string $relation)
    {
        $user = Role::find(1)->$relation;

        $this->assertEquals(21, $user->id);
    }

    #[DataProvider(methodName: 'objectRelationProvider')]
    public function testLazyLoadingWithObjects(string $relation)
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $user = Role::find(1)->$relation;

        $this->assertEquals(21, $user->id);
        $pivot = $user->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
    }

    public function testEmptyLazyLoading()
    {
        DB::enableQueryLog();

        $user = (new Role())->user;

        $this->assertNull($user);
        $this->assertEmpty(DB::getQueryLog());
    }

    public function testLazyLoadingWithDefault()
    {
        $user = Role::find(4)->userWithDefault;

        $this->assertInstanceOf(User::class, $user);
        $this->assertFalse($user->exists);
    }

    #[DataProvider(methodName: 'idRelationProvider')]
    public function testEagerLoading(string $relation)
    {
        $roles = Role::with($relation)->get();

        $this->assertEquals(21, $roles[0]->$relation->id);
        $this->assertEquals(23, $roles[1]->$relation->id);
    }

    #[DataProvider(methodName: 'objectRelationProvider')]
    public function testEagerLoadingWithObjects(string $relation)
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $roles = Role::with($relation)->get();

        $this->assertEquals(21, $roles[0]->$relation->id);
        $this->assertEquals(23, $roles[1]->$relation->id);
        $pivot = $roles[0]->$relation->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
        $this->assertEquals(['role' => ['active' => false]], $roles[2]->$relation->pivot->getAttributes());
    }

    #[DataProvider(methodName: 'idRelationProvider')]
    public function testLazyEagerLoading(string $relation)
    {
        $roles = Role::all()->load($relation);

        $this->assertEquals(21, $roles[0]->$relation->id);
        $this->assertEquals(23, $roles[1]->$relation->id);
    }

    #[DataProvider(methodName: 'objectRelationProvider')]
    public function testLazyEagerLoadingWithObjects(string $relation)
    {
        if (in_array($this->connection, ['sqlite', 'sqlsrv'])) {
            $this->markTestSkipped();
        }

        $roles = Role::all()->load($relation);

        $this->assertEquals(21, $roles[0]->$relation->id);
        $this->assertEquals(23, $roles[1]->$relation->id);
        $pivot = $roles[0]->$relation->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['role' => ['active' => true]], $pivot->getAttributes());
        $this->assertEquals(['role' => ['active' => false]], $roles[2]->$relation->pivot->getAttributes());
    }

    public static function idRelationProvider(): array
    {
        return [
            ['user'],
            ['userInColumn'],
        ];
    }

    public static function objectRelationProvider(): array
    {
        return [
            ['userWithObjects'],
            ['userWithObjectsInColumn'],
        ];
    }
}
