<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Tests\Models\Comment;
use Tests\Models\Locale;
use Tests\Models\Post;
use Tests\Models\Role;
use Tests\Models\User;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'test',
            'username' => 'root',
            'password' => 'password',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::schema()->dropAllTables();

        Capsule::schema()->create('roles', function (Blueprint $table) {
            $table->increments('id');
        });

        Capsule::schema()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->json('options');
        });

        Capsule::schema()->create('locales', function (Blueprint $table) {
            $table->increments('id');
        });

        Capsule::schema()->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->json('options');
        });

        Capsule::schema()->create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->json('options');
        });

        Model::unguarded(function () {
            Role::create();
            Role::create();
            Role::create();
            Role::create();

            Locale::create();
            Locale::create();

            User::create(['options' => ['locale_id' => 1, 'role_ids' => [1, 2]]]);
            User::create(['options' => []]);
            User::create(['options' => ['role_ids' => [2, 3]]]);

            Post::create(['options' => ['user_id' => 1, 'recommendations' => [2]]]);
            Post::create(['options' => ['user_id' => 2]]);

            Comment::create(['options' => ['commentable_type' => User::class, 'commentable_id' => 1]]);
            Comment::create(['options' => []]);
        });
    }
}
