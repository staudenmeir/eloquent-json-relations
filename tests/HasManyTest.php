<?php

namespace Tests;

use Tests\Models\Comment;
use Tests\Models\Locale;
use Tests\Models\User;

class HasManyTest extends TestCase
{
    public function testLazyLoading()
    {
        $users = Locale::first()->users;

        $this->assertEquals([1], $users->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $locales = Locale::with('users')->get();

        $this->assertEquals([1], $locales[0]->users->pluck('id')->all());
    }

    public function testLazyEagerLoading()
    {
        $locales = Locale::all()->load('users');

        $this->assertEquals([1], $locales[0]->users->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $locales = Locale::has('users')->get();

        $this->assertEquals([1], $locales->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $comments = Comment::has('children')->get();

        $this->assertEquals([1], $comments->pluck('id')->all());
    }

    public function testSave()
    {
        $user = Locale::first()->users()->save(User::find(2));

        $this->assertEquals(1, $user->locale->id);
    }
}
