<?php

namespace Tests;

use Tests\Models\User;

class BelongsToTest extends TestCase
{
    public function testGet()
    {
        $locale = User::first()->locale;

        $this->assertEquals(1, $locale->id);
    }

    public function testEagerLoading()
    {
        $users = User::with('locale')->get();

        $this->assertEquals(1, $users[0]->locale->id);
    }

    public function testExistenceQuery()
    {
        $users = User::has('locale')->get();

        $this->assertEquals([1], $users->pluck('id')->all());
    }

    public function testAssociate()
    {
        $user = (new User)->locale()->associate(1);

        $this->assertEquals(1, $user->locale->id);
    }

    public function testDissociate()
    {
        $user = User::first()->locale()->dissociate();

        $this->assertNull($user->locale);
    }
}
