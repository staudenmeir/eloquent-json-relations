![CI](https://github.com/staudenmeir/eloquent-json-relations/workflows/CI/badge.svg)
[![Code Coverage](https://scrutinizer-ci.com/g/staudenmeir/eloquent-json-relations/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/staudenmeir/eloquent-json-relations/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/staudenmeir/eloquent-json-relations/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/staudenmeir/eloquent-json-relations/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/staudenmeir/eloquent-json-relations/v/stable)](https://packagist.org/packages/staudenmeir/eloquent-json-relations)
[![Total Downloads](https://poser.pugx.org/staudenmeir/eloquent-json-relations/downloads)](https://packagist.org/packages/staudenmeir/eloquent-json-relations)
[![License](https://poser.pugx.org/staudenmeir/eloquent-json-relations/license)](https://packagist.org/packages/staudenmeir/eloquent-json-relations)

## Introduction
This Laravel Eloquent extension adds support for JSON foreign keys to `BelongsTo`, `HasOne`, `HasMany`, `HasOneThrough`, `HasManyThrough`, `MorphTo`, `MorphOne` and `MorphMany` relationships.  
It also provides [many-to-many](#many-to-many-relationships) relationships with JSON arrays.

## Compatibility

 Database         | Laravel
:-----------------|:----------
 MySQL 5.7+       | 5.5.29+
 MariaDB 10.2+    | 5.8+
 PostgreSQL 9.3+  | 5.5.29+
 [SQLite 3.18+](https://www.sqlite.org/json1.html) | 5.6.35+
 SQL Server 2016+ | 5.6.25+
 
## Installation

    composer require staudenmeir/eloquent-json-relations:"^1.1"

## Usage

- [One-To-Many Relationships](#one-to-many-relationships)
  - [Referential Integrity](#referential-integrity)
- [Many-To-Many Relationships](#many-to-many-relationships)
  - [Array of IDs](#array-of-ids)
  - [Array of Objects](#array-of-objects)
  - [Query Performance](#query-performance)

### One-To-Many Relationships

In this example, `User` has a `BelongsTo` relationship with `Locale`. There is no dedicated column, but the foreign key (`locale_id`) is stored as a property in a JSON field (`users.options`):

```php
class User extends Model
{
    use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

    protected $casts = [
        'options' => 'json',
    ];

    public function locale()
    {
        return $this->belongsTo('App\Locale', 'options->locale_id');
    }
}

class Locale extends Model
{
    use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

    public function users()
    {
        return $this->hasMany('App\User', 'options->locale_id');
    }
}
```

Remember to use the `HasJsonRelationships` trait in both the parent and the related model.

#### Referential Integrity

On [MySQL](https://dev.mysql.com/doc/refman/en/create-table-foreign-keys.html), [MariaDB](https://mariadb.com/kb/en/library/foreign-keys/) and [SQL Server](https://docs.microsoft.com/en-us/sql/relational-databases/tables/specify-computed-columns-in-a-table) you can still ensure referential integrity with foreign keys on generated/computed columns.

Laravel migrations support this feature on MySQL/MariaDB:

```php
Schema::create('users', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->json('options');
    $locale_id = DB::connection()->getQueryGrammar()->wrap('options->locale_id');
    $table->unsignedInteger('locale_id')->storedAs($locale_id);
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

There is a [workaround](https://github.com/staudenmeir/eloquent-json-relations/tree/1.1#referential-integrity) for older versions of Laravel.

### Many-To-Many Relationships

The package also introduces two new relationship types: `BelongsToJson` and `HasManyJson`

On Laravel 5.6.25+, you can use them to implement many-to-many relationships with JSON arrays.

In this example, `User` has a `BelongsToMany` relationship with `Role`. There is no pivot table, but the foreign keys are stored as an array in a JSON field (`users.options`):

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
        return $this->belongsToJson('App\Role', 'options->role_ids');
    }
}

class Role extends Model
{
    use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

    public function users()
    {
       return $this->hasManyJson('App\User', 'options->role_ids');
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
        return $this->belongsToJson('App\Role', 'options->roles[]->role_id');
    }
}

class Role extends Model
{
    use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

    public function users()
    {
       return $this->hasManyJson('App\User', 'options->roles[]->role_id');
    }
}
```

Here, `options->roles` is the path to the JSON array. `role_id` is the name of the foreign key property inside the record object:

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

#### Query Performance

On PostgreSQL, you can improve the query performance with `jsonb` columns and [`GIN` indexes](https://www.postgresql.org/docs/current/datatype-json.html#JSON-INDEXING).

Use this migration when the array of IDs/objects is the column itself (e.g. `users.role_ids`):

```php
Schema::create('users', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->jsonb('role_ids');
    $table->index('role_ids', null, 'gin');
});
```

Use this migration when the array is nested inside an object (e.g. `users.options->role_ids`):

```php
Schema::create('users', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->jsonb('options');
    $table->rawIndex('("options"->\'role_ids\')', 'users_options_index')->algorithm('gin'); // Laravel 7.10.3+
    //$table->index([DB::raw('("options"->\'role_ids\')')], 'users_options_index', 'gin');  // Laravel < 7.10.3
});
```

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CODE OF CONDUCT](.github/CODE_OF_CONDUCT.md) for details.
