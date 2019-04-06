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
    protected function setUp()
    {
        parent::setUp();

        $config = require __DIR__.'/config/database.php';

        $db = new DB;
        $db->addConnection($config[getenv('DB') ?: 'mysql']);
        $db->setAsGlobal();
        $db->bootEloquent();

        $this->migrate();

        $this->seed();
    }

    /**
     * Migrate the database.
     *
     * @return void
     */
    protected function migrate()
    {
        DB::schema()->dropAllTables();

        DB::schema()->create('roles', function (Blueprint $table) {
            $table->increments('id');
        });

        DB::schema()->create('locales', function (Blueprint $table) {
            $table->increments('id');
        });

        DB::schema()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->json('options');
        });

        DB::schema()->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->json('options');
        });

        DB::schema()->create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->json('options');
        });

        DB::schema()->create('teams', function (Blueprint $table) {
            $table->increments('id');
            $table->json('options');
        });

        DB::schema()->create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->json('options');
            $table->softDeletes();
        });

        DB::schema()->create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->json('options');
        });
    }

    /**
     * Seed the database.
     *
     * @return void
     */
    protected function seed()
    {
        Model::unguard();

        Role::create();
        Role::create();
        Role::create();
        Role::create();

        Locale::create();
        Locale::create();

        User::create([
            'options' => [
                'locale_id' => 1,
                'role_ids' => [1, 2],
                'roles' => [
                    ['role' => ['id' => 1, 'active' => true]],
                    ['role' => ['id' => 2, 'active' => false]],
                ],
            ],
        ]);
        User::create(['options' => ['team_id' => 1]]);
        User::create([
            'options' => [
                'role_ids' => [2, 3],
                'roles' => [
                    ['role' => ['id' => 2, 'active' => true]],
                    ['role' => ['id' => 3, 'active' => false]],
                ],
            ],
        ]);

        Post::create([
            'options' => [
                'user_id' => 1,
                'recommendation_ids' => [2],
                'recommendations' => [
                    ['post_id' => 2],
                ],
            ],
        ]);
        Post::create(['options' => ['user_id' => 2]]);

        Comment::create(['options' => ['commentable_type' => Post::class, 'commentable_id' => 1]]);
        Comment::create(['options' => ['parent_id' => 1]]);
        Comment::create(['options' => ['commentable_type' => User::class, 'commentable_id' => 2]]);

        Team::create(['options' => ['owner_id' => 1]]);
        Team::create(['options' => []]);

        Category::create(['options' => []]);
        Category::create(['options' => ['parent_id' => 1]]);

        Product::create(['options' => ['category_id' => 2]]);
        Product::create(['options' => []]);

        Model::reguard();
    }
}
