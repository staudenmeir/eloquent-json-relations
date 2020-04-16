<?php

namespace Tests;

use Tests\Models\Category;
use Tests\Models\Locale;
use Tests\Models\User;

class HasOneThroughTest extends TestCase
{
    public function testLazyLoading()
    {
        $post = Locale::first()->post;

        $this->assertEquals(1, $post->id);
    }

    public function testEagerLoading()
    {
        $locales = Locale::with('post')->get();

        $this->assertEquals(1, $locales[0]->post->id);
    }

    public function testLazyEagerLoading()
    {
        $locales = Locale::all()->load('post');

        $this->assertEquals(1, $locales[0]->post->id);
    }

    public function testExistenceQuery()
    {
        $locales = Locale::has('post')->get();

        $this->assertEquals([1], $locales->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $users = User::has('teamMate')->get();

        $this->assertEquals([1], $users->pluck('id')->all());
    }

    public function testExistenceQueryForThroughSelfRelation()
    {
        $categories = Category::has('subProduct')->get();

        $this->assertEquals(['42bbcb40-399e-4fa0-b50c-20051d43c7eb'], $categories->pluck('id')->all());
    }
}
