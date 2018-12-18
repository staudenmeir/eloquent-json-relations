<?php

namespace Tests;

use Tests\Models\Comment;
use Tests\Models\Post;

class MorphManyTest extends TestCase
{
    public function testLazyLoading()
    {
        $comments = Post::first()->comments;

        $this->assertEquals([1], $comments->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $posts = Post::with('comments')->get();

        $this->assertEquals([1], $posts[0]->comments->pluck('id')->all());
    }

    public function testLazyEagerLoading()
    {
        $posts = Post::all()->load('comments');

        $this->assertEquals([1], $posts[0]->comments->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $posts = Post::has('comments')->get();

        $this->assertEquals([1], $posts->pluck('id')->all());
    }

    public function testSave()
    {
        $comment = Post::first()->comments()->save(Comment::find(2));

        $this->assertEquals(1, $comment->commentable->id);
    }
}
