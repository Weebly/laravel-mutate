Eloquent Mutators
=================

[![License](https://img.shields.io/packagist/l/Weebly/laravel-mutate.svg)](https://packagist.org/packages/weebly/laravel-mutate)
[![Latest Stable Version](https://img.shields.io/packagist/v/Weebly/laravel-mutate.svg)](https://packagist.org/packages/weebly/laravel-mutate)
[![StyleCI](https://styleci.io/repos/102659341/shield?branch=master)](https://styleci.io/repos/102659341)
![Tests](https://github.com/Weebly/laravel-mutate/actions/workflows/test.yaml/badge.svg)


This package allows you to map your model attributes to database columns when the type in the PHP model does not match the type in the database column.

This could be using `$model->ip_address` as a `string` in your eloquent model but storing it as a `BINARY(16)` in the database for efficiency. Or having a string always encrypted in the DB but readable in clear form within your models.

Installing
----------

```bash
$ composer require weebly/laravel-mutate
```

To use this package, you'll need to add the `ServiceProvider` to the providers array
in `config/app.php` if you are not using automatic package discovery:

```php
Weebly\Mutate\LaravelMutatorServiceProvider::class

```

You'll also need to publish the config to `config/mutators.php` with:

```bash
$ php artisan vendor:publish --provider='Weebly\Mutate\LaravelMutatorServiceProvider'

```

Usage
-----

When creating an Eloquent model, you'll need to extend `Weebly\Mutate\Database\Model`
and add `$mutate` property it:

```php
<?php

namespace App\Models;

use Weebly\Mutate\Database\Model;

class User extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'users';

    /**
     * {@inheritdoc}
     */
    protected $mutate = [
        'id' => 'uuid_v1_binary'
    ];
}
```

This will automatically serialize/unserialize the `id` attribute on the `User` model when
getting/setting the attribute from the database. This allows you to no longer need to set
accessors/mutator methods on the model directly.

> **Note:**  Unlike the built in Laravel accessors/mutators,
this package will serialize the attribute values when they are passed to an Eloquent query builder.

Included Mutators
-----------------

- `uuid_v1_binary` Will take a uuid version 1, re-order its bytes so that if uuidA was generated before uuidB, then storedUuidA < storedUuidB, and store it in the database as 16 bytes of data. For more information on the re-ordering of bytes, see: https://www.percona.com/blog/2014/12/19/store-uuid-optimized-way/.
- `ip_binary` Will take a string representation of an IPv4 or IPv6 and store it as 16 bytes in the database.
- `encrypt_string` Will take a non encrypted string and encrypt it when going to the database.
- `hex_binary` Will take any hexadecimal string attribute and store it as binary data.
- `unix_timestamp` Will take a Carbon date but store it as an integer unix timestamp.

Creating Custom Mutators
------------------------

To define a custom mutator, you'll need to create a class that implements
`Weebly\Mutate\Mutators\MutatorContract`, and add it to the `enabled` array in `config/mutators.php`.

> **Note:** All attributes are cached on a model instance automatically, so you should not need to add
any caching logic at the mutator level.

When building and registering a Mutator, it is important to know that they
are resolved automatically from the Laravel IOC container, which means you may create
service providers for them if they require custom constructor arguments.

```php
<?php

namespace App\Mutators;

use Weebly\Mutate\Mutators\MutatorContract;

class ExampleEncryptMutator implements MutatorContract
{
    /**
     * {@inheritdoc}
     */
    public function serializeAttribute($value)
    {
        return encrypt($value);
    }

    /**
     * {@inheritdoc}
     */
    public function unserializeAttribute($value)
    {
        return decrypt($value);
    }
}
```

Testing
-------

Running tests:

```bash
$ ./vendor/bin/phpunit
```

License
-------

This package is open-sourced software licensed under the [2-Clause BSD](https://opensource.org/licenses/BSD-2-Clause) license.
