<?php

namespace Tests;

use Tests\Models\Locale;
use Tests\Models\User;

class HasManyTest extends TestCase
{
    public function testGet()
    {
        $users = Locale::first()->users;

        $this->assertEquals([1], $users->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $locales = Locale::with('users')->get();

        $this->assertEquals([1], $locales[0]->users->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $locales = Locale::has('users')->get();

        $this->assertEquals([1], $locales->pluck('id')->all());
    }

    public function testSave()
    {
        $user = Locale::first()->users()->save(User::find(2));

        $this->assertEquals(1, $user->locale->id);
    }
}
