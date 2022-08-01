<?php

namespace Tests;

use Tests\Models\Comment;
use Tests\Models\Post;

class MorphOneTest extends TestCase
{
    public function testLazyLoading()
    {
        $comment = Post::find(31)->comment;

        $this->assertEquals(41, $comment->id);
    }

    public function testEagerLoading()
    {
        $posts = Post::with('comment')->get();

        $this->assertEquals(41, $posts[0]->comment->id);
    }

    public function testLazyEagerLoading()
    {
        $posts = Post::all()->load('comment');

        $this->assertEquals(41, $posts[0]->comment->id);
    }

    public function testExistenceQuery()
    {
        $posts = Post::has('comment')->get();

        $this->assertEquals([31], $posts->pluck('id')->all());
    }

    public function testSave()
    {
        $comment = Post::find(31)->comment()->save(Comment::find(42));

        $this->assertEquals(31, $comment->commentable->id);
    }
}
