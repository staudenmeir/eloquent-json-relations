<?php

namespace Tests;

use Tests\Models\Category;
use Tests\Models\Locale;
use Tests\Models\User;

class HasManyThroughTest extends TestCase
{
    public function testLazyLoading()
    {
        $posts = Locale::find(11)->posts;

        $this->assertEquals([31], $posts->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $locales = Locale::with('posts')->get();

        $this->assertEquals([31], $locales[0]->posts->pluck('id')->all());
    }

    public function testLazyEagerLoading()
    {
        $locales = Locale::all()->load('posts');

        $this->assertEquals([31], $locales[0]->posts->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $locales = Locale::has('posts')->get();

        $this->assertEquals([11], $locales->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $users = User::has('teamMates')->get();

        $this->assertEquals([21], $users->pluck('id')->all());
    }

    public function testExistenceQueryForThroughSelfRelation()
    {
        $categories = Category::has('subProducts')->get();

        $this->assertEquals(['42bbcb40-399e-4fa0-b50c-20051d43c7eb'], $categories->pluck('id')->all());
    }
}
