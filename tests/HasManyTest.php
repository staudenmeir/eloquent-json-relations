<?php

namespace Tests;

use Tests\Models\Comment;
use Tests\Models\Locale;
use Tests\Models\User;

class HasManyTest extends TestCase
{
    public function testLazyLoading()
    {
        $users = Locale::find(11)->users;

        $this->assertEquals([21], $users->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $locales = Locale::with('users')->get();

        $this->assertEquals([21], $locales[0]->users->pluck('id')->all());
    }

    public function testLazyEagerLoading()
    {
        $locales = Locale::all()->load('users');

        $this->assertEquals([21], $locales[0]->users->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $locales = Locale::has('users')->get();

        $this->assertEquals([11], $locales->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $comments = Comment::has('children')->get();

        $this->assertEquals([41], $comments->pluck('id')->all());
    }

    public function testSave()
    {
        $user = Locale::find(11)->users()->save(User::find(22));

        $this->assertEquals(11, $user->locale->id);
    }
}
