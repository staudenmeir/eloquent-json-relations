<?php

namespace Tests;

use Tests\Models\Comment;
use Tests\Models\User;

class MorphToTest extends TestCase
{
    public function testLazyLoading()
    {
        $commentable = Comment::find(41)->commentable;

        $this->assertEquals(31, $commentable->id);
    }

    public function testEagerLoading()
    {
        $comments = Comment::with('commentable')->get();

        $this->assertEquals(31, $comments[0]->commentable->id);
    }

    public function testLazyEagerLoading()
    {
        $comments = Comment::all()->load('commentable');

        $this->assertEquals(31, $comments[0]->commentable->id);
    }

    public function testAssociate()
    {
        $comment = (new Comment())->commentable()->associate(User::find(21));

        $this->assertEquals(21, $comment->commentable->id);
    }

    public function testDissociate()
    {
        $comment = Comment::find(41)->commentable()->dissociate();

        $this->assertNull($comment->commentable);
    }
}
