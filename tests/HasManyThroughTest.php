<?php

namespace Tests;

use Tests\Models\Locale;

class HasManyThroughTest extends TestCase
{
    public function testLazyLoading()
    {
        $users = Locale::first()->posts;

        $this->assertEquals([1], $users->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $locales = Locale::with('posts')->get();

        $this->assertEquals([1], $locales[0]->posts->pluck('id')->all());
    }

    public function testLazyEagerLoading()
    {
        $locales = Locale::all()->load('posts');

        $this->assertEquals([1], $locales[0]->posts->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $locales = Locale::has('posts')->get();

        $this->assertEquals([1], $locales->pluck('id')->all());
    }
}
