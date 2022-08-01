<?php

namespace Tests;

use Tests\Models\Comment;
use Tests\Models\Post;

class MorphManyTest extends TestCase
{
    public function testLazyLoading()
    {
        $comments = Post::find(31)->comments;

        $this->assertEquals([41], $comments->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $posts = Post::with('comments')->get();

        $this->assertEquals([41], $posts[0]->comments->pluck('id')->all());
    }

    public function testLazyEagerLoading()
    {
        $posts = Post::all()->load('comments');

        $this->assertEquals([41], $posts[0]->comments->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $posts = Post::has('comments')->get();

        $this->assertEquals([31], $posts->pluck('id')->all());
    }

    public function testSave()
    {
        $comment = Post::find(31)->comments()->save(Comment::find(42));

        $this->assertEquals(31, $comment->commentable->id);
    }
}
