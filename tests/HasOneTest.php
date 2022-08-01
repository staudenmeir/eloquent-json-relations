<?php

namespace Tests;

use Tests\Models\Comment;
use Tests\Models\Locale;
use Tests\Models\User;

class HasOneTest extends TestCase
{
    public function testLazyLoading()
    {
        $user = Locale::find(11)->user;

        $this->assertEquals(21, $user->id);
    }

    public function testEagerLoading()
    {
        $locales = Locale::with('user')->get();

        $this->assertEquals(21, $locales[0]->user->id);
    }

    public function testLazyEagerLoading()
    {
        $locales = Locale::all()->load('user');

        $this->assertEquals(21, $locales[0]->user->id);
    }

    public function testExistenceQuery()
    {
        $locales = Locale::has('user')->get();

        $this->assertEquals([11], $locales->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $comments = Comment::has('child')->get();

        $this->assertEquals([41], $comments->pluck('id')->all());
    }

    public function testSave()
    {
        $user = Locale::find(11)->user()->save(User::find(22));

        $this->assertEquals(11, $user->locale->id);
    }
}
