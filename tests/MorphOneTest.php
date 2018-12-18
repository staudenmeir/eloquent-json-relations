<?php

namespace Tests;

use Tests\Models\Comment;
use Tests\Models\Post;

class MorphOneTest extends TestCase
{
    public function testLazyLoading()
    {
        $comment = Post::first()->comment;

        $this->assertEquals(1, $comment->id);
    }

    public function testEagerLoading()
    {
        $posts = Post::with('comment')->get();

        $this->assertEquals(1, $posts[0]->comment->id);
    }

    public function testLazyEagerLoading()
    {
        $posts = Post::all()->load('comment');

        $this->assertEquals(1, $posts[0]->comment->id);
    }

    public function testExistenceQuery()
    {
        $posts = Post::has('comment')->get();

        $this->assertEquals([1], $posts->pluck('id')->all());
    }

    public function testSave()
    {
        $comment = Post::first()->comment()->save(Comment::find(2));

        $this->assertEquals(1, $comment->commentable->id);
    }
}
