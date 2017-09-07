Eloquent Mutators
=================

Installing
----------

```bash
$ composer require weebly/laravel-mutate
```

To use this package, you'll need to add the `ServiceProvider` to the providers array
in `config/app.php`

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
     * {@inheritDoc}
     */
    protected $table = 'users';
    
    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function serializeAttribute($value)
    {
        return encrypt($value);
    }

    /**
     * {@inheritDoc}
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
