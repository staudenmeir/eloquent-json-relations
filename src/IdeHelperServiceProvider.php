<?php

namespace Staudenmeir\EloquentJsonRelations;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Staudenmeir\EloquentJsonRelations\IdeHelper\JsonRelationsHook;

class IdeHelperServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $this->app->get('config');

        $config->set(
            'ide-helper.model_hooks',
            array_merge(
                [JsonRelationsHook::class],
                $config->get('ide-helper.model_hooks', [])
            )
        );
    }

    public function provides(): array
    {
        return [
            ModelsCommand::class,
        ];
    }
}
