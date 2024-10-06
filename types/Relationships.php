<?php

namespace Staudenmeir\EloquentJsonRelations\Types;

use Staudenmeir\EloquentJsonRelations\JsonKey;
use Staudenmeir\EloquentJsonRelations\Types\Models\Project;
use Staudenmeir\EloquentJsonRelations\Types\Models\Role;
use Staudenmeir\EloquentJsonRelations\Types\Models\User;

use function PHPStan\Testing\assertType;

function test(Role $role, User $user): void
{
    assertType(
        'Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson<Staudenmeir\EloquentJsonRelations\Types\Models\Role, Staudenmeir\EloquentJsonRelations\Types\Models\User>',
        $user->belongsToJson(Role::class, 'role_ids')
    );
    
    assertType(
        'Staudenmeir\EloquentJsonRelations\Relations\HasManyJson<Staudenmeir\EloquentJsonRelations\Types\Models\User, Staudenmeir\EloquentJsonRelations\Types\Models\Role>',
        $role->hasManyJson(User::class, 'role_ids')
    );

    assertType(
        'Staudenmeir\EloquentJsonRelations\Relations\HasOneJson<Staudenmeir\EloquentJsonRelations\Types\Models\User, Staudenmeir\EloquentJsonRelations\Types\Models\Role>',
        $role->hasOneJson(User::class, 'role_ids')
    );

    assertType(
        'Staudenmeir\EloquentHasManyDeep\HasManyDeep<Staudenmeir\EloquentJsonRelations\Types\Models\Project, Staudenmeir\EloquentJsonRelations\Types\Models\Role>',
        $role->hasManyThroughJson(Project::class, User::class, new JsonKey('role_ids'))
    );
}
