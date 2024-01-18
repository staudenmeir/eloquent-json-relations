<?php

namespace Tests\IdeHelper;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Illuminate\Database\Capsule\Manager as DB;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Orchestra\Testbench\TestCase;
use Staudenmeir\EloquentJsonRelations\IdeHelper\JsonRelationsHook;
use Tests\IdeHelper\Models\Role;
use Tests\IdeHelper\Models\User;

class JsonRelationsHookTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__.'/../config/database.php';

        $db = new DB();
        $db->addConnection($config[getenv('DB_CONNECTION') ?: 'sqlite']);
        $db->setAsGlobal();
        $db->bootEloquent();
    }

    public function testRunWithBelongsToJson()
    {
        $command = Mockery::mock(ModelsCommand::class);
        $command->shouldReceive('setProperty')->once()->with(
            'roles',
            '\Illuminate\Database\Eloquent\Collection|\Tests\IdeHelper\Models\Role[]',
            true,
            false
        );
        $command->shouldReceive('setProperty')->once()->with(
            'roles_count',
            'int',
            true,
            false,
            null,
            true
        );

        $hook = new JsonRelationsHook();
        $hook->run($command, new User());
    }

    public function testRunWithHasManyJson()
    {
        $command = Mockery::mock(ModelsCommand::class);
        $command->shouldReceive('setProperty')->once()->with(
            'users',
            '\Illuminate\Database\Eloquent\Collection|\Tests\IdeHelper\Models\User[]',
            true,
            false
        );
        $command->shouldReceive('setProperty')->once()->with(
            'users_count',
            'int',
            true,
            false,
            null,
            true
        );

        $hook = new JsonRelationsHook();
        $hook->run($command, new Role());
    }
}
