<?php

namespace Tests;

use Tests\Models\Comment;
use Tests\Models\User;

class MorphToTest extends TestCase
{
    public function testLazyLoading()
    {
        $commentable = Comment::first()->commentable;

        $this->assertEquals(1, $commentable->id);
    }

    public function testEagerLoading()
    {
        $comments = Comment::with('commentable')->get();

        $this->assertEquals(1, $comments[0]->commentable->id);
    }

    public function testLazyEagerLoading()
    {
        $comments = Comment::all()->load('commentable');

        $this->assertEquals(1, $comments[0]->commentable->id);
    }

    public function testAssociate()
    {
        $comment = (new Comment())->commentable()->associate(User::find(1));

        $this->assertEquals(1, $comment->commentable->id);
    }

    public function testDissociate()
    {
        $comment = Comment::first()->commentable()->dissociate();

        $this->assertNull($comment->commentable);
    }
}
