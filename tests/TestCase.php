<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\Attributes\IgnoreMethodForCodeCoverage;
use PHPUnit\Framework\TestCase as Base;
use Staudenmeir\EloquentJsonRelations\Grammars\PostgresGrammar;
use Staudenmeir\EloquentJsonRelations\Grammars\SqlServerGrammar;
use Tests\Models\Category;
use Tests\Models\Comment;
use Tests\Models\Country;
use Tests\Models\Locale;
use Tests\Models\Permission;
use Tests\Models\Post;
use Tests\Models\Product;
use Tests\Models\Role;
use Tests\Models\Team;
use Tests\Models\Project;
use Tests\Models\User;

#[IgnoreMethodForCodeCoverage(PostgresGrammar::class, 'compileMemberOf')]
#[IgnoreMethodForCodeCoverage(PostgresGrammar::class, 'prepareBindingsForMemberOf')]
#[IgnoreMethodForCodeCoverage(SqlServerGrammar::class, 'compileMemberOf')]
#[IgnoreMethodForCodeCoverage(SqlServerGrammar::class, 'prepareBindingsForMemberOf')]
abstract class TestCase extends Base
{
    protected string $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = getenv('DATABASE') ?: 'mysql';

        $config = require __DIR__.'/config/database.php';

        $db = new DB();
        $db->addConnection($config[$this->database]);
        $db->setAsGlobal();
        $db->bootEloquent();

        $this->migrate();

        $this->seed();
    }

    protected function tearDown(): void
    {
        DB::connection()->disconnect();

        parent::tearDown();
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
            $table->unsignedInteger('country_id');
            $table->json('options');
            $table->json('role_ids');
            $table->json('role_objects');
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

        DB::schema()->create('countries', function (Blueprint $table) {
            $table->unsignedInteger('id');
        });

        DB::schema()->create('permissions', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('role_id');
        });

        DB::schema()->create('projects', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('user_id');
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
            'country_id' => 71,
            'options' => [
                'locale_id' => 11,
                'role_ids' => [1, 2],
                'roles' => [
                    ['role' => ['id' => 1, 'active' => true]],
                    ['role' => ['id' => 2, 'active' => false]],
                    ['foo' => 'bar'],
                ],
            ],
            'role_ids' => [1, 2],
            'role_objects' => [
                ['role' => ['id' => 1, 'active' => true]],
                ['role' => ['id' => 2, 'active' => false]],
                ['foo' => 'bar'],
            ],
        ]);
        User::create([
            'id' => 22,
            'country_id' => 72,
            'options' => ['team_id' => 51],
            'role_ids' => [],
            'role_objects' => [],
        ]);
        User::create([
            'id' => 23,
            'country_id' => 73,
            'options' => [
                'role_ids' => [2, 3],
                'roles' => [
                    ['role' => ['id' => 2, 'active' => true]],
                    ['role' => ['id' => 3, 'active' => false]],
                ],
            ],
            'role_ids' => [2, 3],
            'role_objects' => [
                ['role' => ['id' => 2, 'active' => true]],
                ['role' => ['id' => 3, 'active' => false]],
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

        Country::create(['id' => 71]);
        Country::create(['id' => 72]);
        Country::create(['id' => 73]);

        Permission::create(['id' => 81, 'role_id' => 1]);
        Permission::create(['id' => 82, 'role_id' => 1]);
        Permission::create(['id' => 83, 'role_id' => 2]);
        Permission::create(['id' => 84, 'role_id' => 3]);
        Permission::create(['id' => 85, 'role_id' => 4]);

        Project::create([
             'id' => 71,
             'user_id' => 21,
         ]);
        Project::create([
             'id' => 72,
             'user_id' => 22,
         ]);
        Project::create([
             'id' => 73,
             'user_id' => 23,
         ]);

        Model::reguard();
    }
}
