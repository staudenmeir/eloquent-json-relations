<?php

namespace Tests;

use Tests\Models\Comment;
use Tests\Models\User;

class MorphManyTest extends TestCase
{
    public function testGet()
    {
        $comments = User::first()->comments;

        $this->assertEquals([1], $comments->pluck('id')->all());
    }

    public function testEagerLoading()
    {
        $users = User::with('comments')->get();

        $this->assertEquals([1], $users[0]->comments->pluck('id')->all());
    }

    public function testExistenceQuery()
    {
        $users = User::has('comments')->get();

        $this->assertEquals([1], $users->pluck('id')->all());
    }

    public function testSave()
    {
        $comment = User::first()->comments()->save(Comment::find(2));

        $this->assertEquals(1, $comment->commentable->id);
    }
}
