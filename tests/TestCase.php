<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase as Base;
use Tests\Models\Category;
use Tests\Models\Comment;
use Tests\Models\Locale;
use Tests\Models\Post;
use Tests\Models\Product;
use Tests\Models\Role;
use Tests\Models\Team;
use Tests\Models\User;

abstract class TestCase extends Base
{
    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__.'/config/database.php';

        $db = new DB();
        $db->addConnection($config[getenv('DATABASE') ?: 'mysql']);
        $db->setAsGlobal();
        $db->bootEloquent();

        $this->migrate();

        $this->seed();
    }

    protected function migrate(): void
    {
        DB::schema()->dropAllTables();

        DB::schema()->create('roles', function (Blueprint $table) {
            $table->id();
        });

        DB::schema()->create('locales', function (Blueprint $table) {
            $table->unsignedInteger('id');
        });

        DB::schema()->create('users', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->json('options');
        });

        DB::schema()->create('posts', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->json('options');
        });

        DB::schema()->create('comments', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->json('options');
        });

        DB::schema()->create('teams', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->json('options');
        });

        DB::schema()->create('categories', function (Blueprint $table) {
            $type = DB::connection()->getDriverName() === 'pgsql' ? 'uuid' : 'string';
            $table->$type('id');
            $table->json('options');
            $table->softDeletes();
        });

        DB::schema()->create('products', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->json('options');
        });
    }

    protected function seed(): void
    {
        Model::unguard();

        Role::create();
        Role::create();
        Role::create();
        Role::create();

        Locale::create(['id' => 11]);
        Locale::create(['id' => 12]);

        User::create([
            'id' => 21,
            'options' => [
                'locale_id' => 11,
                'role_ids' => [1, 2],
                'roles' => [
                    ['role' => ['id' => 1, 'active' => true]],
                    ['role' => ['id' => 2, 'active' => false]],
                    ['foo' => 'bar'],
                ],
            ],
        ]);
        User::create(['id' => 22, 'options' => ['team_id' => 51]]);
        User::create([
            'id' => 23,
            'options' => [
                'role_ids' => [2, 3],
                'roles' => [
                    ['role' => ['id' => 2, 'active' => true]],
                    ['role' => ['id' => 3, 'active' => false]],
                ],
            ],
        ]);

        Post::create([
            'id' => 31,
            'options' => [
                'user_id' => 21,
                'recommendation_ids' => [32],
                'recommendations' => [
                    ['post_id' => 32],
                ],
            ],
        ]);
        Post::create(['id' => 32, 'options' => ['user_id' => 22]]);

        Comment::create(['id' => 41, 'options' => ['commentable_type' => Post::class, 'commentable_id' => 31]]);
        Comment::create(['id' => 42, 'options' => ['parent_id' => 41]]);
        Comment::create(['id' => 43, 'options' => ['commentable_type' => User::class, 'commentable_id' => 22]]);

        Team::create(['id' => 51, 'options' => ['owner_id' => 21]]);
        Team::create(['id' => 52, 'options' => []]);

        Category::create([
            'id' => '42bbcb40-399e-4fa0-b50c-20051d43c7eb',
            'options' => [],
        ]);
        Category::create([
            'id' => 'af5811f8-45ae-43a9-b333-c936890973cb',
            'options' => ['parent_id' => '42bbcb40-399e-4fa0-b50c-20051d43c7eb'],
        ]);

        Product::create(['id' => 61, 'options' => ['category_id' => 'af5811f8-45ae-43a9-b333-c936890973cb']]);
        Product::create(['id' => 62, 'options' => []]);

        Model::reguard();
    }
}
