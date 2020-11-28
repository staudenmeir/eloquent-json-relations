<?php

namespace Tests;

use Tests\Models\Comment;
use Tests\Models\Locale;
use Tests\Models\User;

class HasOneTest extends TestCase
{
    public function testLazyLoading()
    {
        $user = Locale::first()->user;

        $this->assertEquals(1, $user->id);
    }

    public function testEagerLoading()
    {
        $locales = Locale::with('user')->get();

        $this->assertEquals(1, $locales[0]->user->id);
    }

    public function testLazyEagerLoading()
    {
        $locales = Locale::all()->load('user');

        $this->assertEquals(1, $locales[0]->user->id);
    }

    public function testExistenceQuery()
    {
        $locales = Locale::has('user')->get();

        $this->assertEquals([1], $locales->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $comments = Comment::has('child')->get();

        $this->assertEquals([1], $comments->pluck('id')->all());
    }

    public function testSave()
    {
        $user = Locale::first()->user()->save(User::find(2));

        $this->assertEquals(1, $user->locale->id);
    }
}
