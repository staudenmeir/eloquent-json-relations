# Eloquent JSON Relations

[![CI](https://github.com/staudenmeir/eloquent-json-relations/actions/workflows/ci.yml/badge.svg)](https://github.com/staudenmeir/eloquent-json-relations/actions/workflows/ci.yml)
[![Code Coverage](https://codecov.io/gh/staudenmeir/eloquent-json-relations/graph/badge.svg?token=T41IX53I5U)](https://codecov.io/gh/staudenmeir/eloquent-json-relations)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/staudenmeir/eloquent-json-relations/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/staudenmeir/eloquent-json-relations/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/staudenmeir/eloquent-json-relations/v/stable)](https://packagist.org/packages/staudenmeir/eloquent-json-relations)
[![Total Downloads](https://poser.pugx.org/staudenmeir/eloquent-json-relations/downloads)](https://packagist.org/packages/staudenmeir/eloquent-json-relations/stats)
[![License](https://poser.pugx.org/staudenmeir/eloquent-json-relations/license)](https://github.com/staudenmeir/eloquent-json-relations/blob/master/LICENSE)

This Laravel Eloquent extension adds support for JSON foreign keys to `BelongsTo`, `HasOne`, `HasMany`, `HasOneThrough`
, `HasManyThrough`, `MorphTo`, `MorphOne` and `MorphMany` relationships.

It also provides [many-to-many](#many-to-many-relationships) and [has-many-through](#has-many-through-relationships)
relationships with JSON arrays.

## Compatibility

| Database                                          | Laravel |
|:--------------------------------------------------|:--------|
| MySQL 5.7+                                        | 5.5.29+ |
| MariaDB 10.2+                                     | 5.8+    |
| PostgreSQL 9.3+                                   | 5.5.29+ |
| [SQLite 3.18+](https://www.sqlite.org/json1.html) | 5.6.35+ |
| SQL Server 2016+                                  | 5.6.25+ |

## Installation

    composer require "staudenmeir/eloquent-json-relations:^1.1"

Use this command if you are in PowerShell on Windows (e.g. in VS Code):

    composer require "staudenmeir/eloquent-json-relations:^^^^1.1"

## Versions

| Laravel | Package |
|:--------|:--------|
| 11.x    | 1.11    |
| 10.x    | 1.8     |
| 9.x     | 1.7     |
| 8.x     | 1.6     |
| 7.x     | 1.5     |
| 6.x     | 1.4     |
| 5.8     | 1.3     |
| 5.5–5.7 | 1.2     |

## Usage

- [One-To-Many Relationships](#one-to-many-relationships)
    - [Referential Integrity](#referential-integrity)
- [Many-To-Many Relationships](#many-to-many-relationships)
    - [Array of IDs](#array-of-ids)
    - [Array of Objects](#array-of-objects)
    - [HasOneJson](#hasonejson)
    - [Composite Keys](#composite-keys)
    - [Query Performance](#query-performance)
- [Has-Many-Through Relationships](#has-many-through-relationships)
- [Deep Relationship Concatenation](#deep-relationship-concatenation)

### One-To-Many Relationships

In this example, `User` has a `BelongsTo` relationship with `Locale`. There is no dedicated column, but the foreign
key (`locale_id`) is stored as a property in a JSON field (`users.options`):

```php
class User extends Model
{
    use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

    protected $casts = [
        'options' => 'json',
    ];

    public function locale()
    {
        return $this->belongsTo(Locale::class, 'options->locale_id');
    }
}

class Locale extends Model
{
    use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

    public function users()
    {
        return $this->hasMany(User::class, 'options->locale_id');
    }
}
```

Remember to use the `HasJsonRelationships` trait in both the parent and the related model.

#### Referential Integrity

On [MySQL](https://dev.mysql.com/doc/refman/en/create-table-foreign-keys.html)
, [MariaDB](https://mariadb.com/kb/en/library/foreign-keys/)
and [SQL Server](https://docs.microsoft.com/en-us/sql/relational-databases/tables/specify-computed-columns-in-a-table)
you can still ensure referential integrity with foreign keys on generated/computed columns.

Laravel migrations support this feature on MySQL/MariaDB:

```php
Schema::create('users', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->json('options');
    $locale_id = DB::connection()->getQueryGrammar()->wrap('options->locale_id');
    $table->unsignedBigInteger('locale_id')->storedAs($locale_id);
    $table->foreign('locale_id')->references('id')->on('locales');
});
```

Laravel migrations (5.7.25+) also support this feature on SQL Server:

```php
Schema::create('users', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->json('options');
    $locale_id = DB::connection()->getQueryGrammar()->wrap('options->locale_id');
    $locale_id = 'CAST('.$locale_id.' AS INT)';
    $table->computed('locale_id', $locale_id)->persisted();
    $table->foreign('locale_id')->references('id')->on('locales');
});
```

There is a [workaround](https://github.com/staudenmeir/eloquent-json-relations/tree/1.1#referential-integrity) for older
versions of Laravel.

### Many-To-Many Relationships

The package also introduces two new relationship types: `BelongsToJson` and `HasManyJson`

Use them to implement many-to-many relationships with JSON arrays.

In this example, `User` has a `BelongsToMany` relationship with `Role`. There is no pivot table, but the foreign keys
are stored as an array in a JSON field (`users.options`):

#### Array of IDs

By default, the relationship stores pivot records as an array of IDs:

```php
class User extends Model
{
    use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

    protected $casts = [
       'options' => 'json',
    ];
    
    public function roles()
    {
        return $this->belongsToJson(Role::class, 'options->role_ids');
    }
}

class Role extends Model
{
    use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

    public function users()
    {
       return $this->hasManyJson(User::class, 'options->role_ids');
    }
}
```

On the side of the `BelongsToJson` relationship, you can use `attach()`, `detach()`, `sync()` and `toggle()`:

```php
$user = new User;
$user->roles()->attach([1, 2])->save(); // Now: [1, 2]

$user->roles()->detach([2])->save();    // Now: [1]

$user->roles()->sync([1, 3])->save();   // Now: [1, 3]

$user->roles()->toggle([2, 3])->save(); // Now: [1, 2]
```

#### Array of Objects

You can also store pivot records as objects with additional attributes:

```php
class User extends Model
{
    use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

    protected $casts = [
       'options' => 'json',
    ];
    
    public function roles()
    {
        return $this->belongsToJson(Role::class, 'options->roles[]->role_id');
    }
}

class Role extends Model
{
    use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

    public function users()
    {
       return $this->hasManyJson(User::class, 'options->roles[]->role_id');
    }
}
```

Here, `options->roles` is the path to the JSON array. `role_id` is the name of the foreign key property inside the
record object:

```php
$user = new User;
$user->roles()->attach([1 => ['active' => true], 2 => ['active' => false]])->save();
// Now: [{"role_id":1,"active":true},{"role_id":2,"active":false}]

$user->roles()->detach([2])->save();
// Now: [{"role_id":1,"active":true}]

$user->roles()->sync([1 => ['active' => false], 3 => ['active' => true]])->save();
// Now: [{"role_id":1,"active":false},{"role_id":3,"active":true}]

$user->roles()->toggle([2 => ['active' => true], 3])->save();
// Now: [{"role_id":1,"active":false},{"role_id":2,"active":true}]
```

**Limitations:** On SQLite and SQL Server, these relationships only work partially.

#### HasOneJson

Define a `HasOneJson relationship if you only want to retrieve a single related instance:

```php
class Role extends Model
{
use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

    public function latestUser(): \Staudenmeir\EloquentJsonRelations\Relations\HasOneJson
    {
        return $this->hasOneJson(User::class, 'options->roles[]->role_id')
            ->latest();
    }
}
```

#### Composite Keys

If multiple columns need to match, you can define a composite key.

Pass an array of keys that starts with JSON key:

```php
class Employee extends Model
{
    public function tasks()
    {
        return $this->belongsToJson(
            Task::class,
            ['options->work_stream_ids', 'team_id'],
            ['work_stream_id', 'team_id']
        );
    }
}

class Task extends Model
{
    public function employees()
    {
        return $this->hasManyJson(
            Employee::class,
            ['options->work_stream_ids', 'team_id'],
            ['work_stream_id', 'team_id']
        );
    }
}
```

#### Query Performance

##### MySQL

On MySQL 8.0.17+, you can improve the query performance
with [multi-valued indexes](https://dev.mysql.com/doc/refman/8.0/en/create-index.html#create-index-multi-valued).

Use this migration when the array is the column itself (e.g. `users.role_ids`):

```php
Schema::create('users', function (Blueprint $table) {
    // ...
    
    // Array of IDs
    $table->rawIndex('(cast(`role_ids` as unsigned array))', 'users_role_ids_index');
    
    // Array of objects
    $table->rawIndex('(cast(`roles`->\'$[*]."role_id"\' as unsigned array))', 'users_roles_index');
});
```

Use this migration when the array is nested inside an object (e.g. `users.options->role_ids`):

```php
Schema::create('users', function (Blueprint $table) {
    // ...
    
    // Array of IDs
    $table->rawIndex('(cast(`options`->\'$."role_ids"\' as unsigned array))', 'users_role_ids_index');
    
    // Array of objects
    $table->rawIndex('(cast(`options`->\'$."roles"[*]."role_id"\' as unsigned array))', 'users_roles_index');
});
```

MySQL is quite picky about the syntax so I recommend that you check once
with [`EXPLAIN`](https://dev.mysql.com/doc/refman/8.0/en/using-explain.html) that the executed relationship queries
actually use the index.

##### PostgreSQL

On PostgreSQL, you can improve the query performance with `jsonb` columns
and [`GIN` indexes](https://www.postgresql.org/docs/current/datatype-json.html#JSON-INDEXING).

Use this migration when the array of IDs/objects is the column itself (e.g. `users.role_ids`):

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->jsonb('role_ids');
    $table->index('role_ids')->algorithm('gin');
});
```

Use this migration when the array is nested inside an object (e.g. `users.options->role_ids`):

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->jsonb('options');
    $table->rawIndex('("options"->\'role_ids\')', 'users_options_index')->algorithm('gin');
});
```

### Has-Many-Through Relationships

Similar to Laravel's [`HasManyThrough`](https://laravel.com/docs/9.x/eloquent-relationships#has-many-through), you can
define `HasManyThroughJson` relationships when the JSON column is in the intermediate table (Laravel 9+). This
requires [staudenmeir/eloquent-has-many-deep](https://github.com/staudenmeir/eloquent-has-many-deep).

Consider a relationship between `Role` and `Project` through `User`:

`Role` → has many JSON → `User` → has many `Project`

[Install](https://github.com/staudenmeir/eloquent-has-many-deep/#installation) the additional package, add the
`HasRelationships` trait to the parent (first) model and pass the JSON column as a `JsonKey` object:

```php
class Role extends Model
{
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    public function projects()
    {
        return $this->hasManyThroughJson(
            Project::class,
            User::class,
            new \Staudenmeir\EloquentJsonRelations\JsonKey('options->role_ids')
        );
    }
}
```

The reverse relationship would look like this:

```php
class Project extends Model
{
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    public function roles()
    {
        return $this->hasManyThroughJson(
            Role::class, User::class, 'id', 'id', 'user_id', new JsonKey('options->role_ids')
        );
    }
}
```

### Deep Relationship Concatenation

You can include JSON relationships into deep relationships by concatenating them with other relationships
using [staudenmeir/eloquent-has-many-deep](https://github.com/staudenmeir/eloquent-has-many-deep) (Laravel 9+).

Consider a relationship between `User` and `Permission` through `Role`:

`User` → belongs to JSON → `Role` → has many → `Permission`

[Install](https://github.com/staudenmeir/eloquent-has-many-deep/#installation) the additional package, add the
`HasRelationships` trait to the parent (first) model
and [define](https://github.com/staudenmeir/eloquent-has-many-deep/#concatenating-existing-relationships) a
deep relationship:

```php
class User extends Model
{
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

    public function permissions()
    {
        return $this->hasManyDeepFromRelations(
            $this->roles(),
            (new Role)->permissions()
        );
    }
    
    public function roles()
    {
        return $this->belongsToJson(Role::class, 'options->role_ids');
    }
}

class Role extends Model
{
    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}

$permissions = User::find($id)->permissions;
```

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CODE OF CONDUCT](.github/CODE_OF_CONDUCT.md) for details.
