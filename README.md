# Laravel Auditing

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ensi/laravel-auditing.svg?style=flat-square)](https://packagist.org/packages/ensi/laravel-auditing)
[![Tests](https://github.com/ensi-platform/laravel-auditing/actions/workflows/run-tests.yml/badge.svg?branch=master)](https://github.com/ensi-platform/laravel-auditing/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/ensi/laravel-auditing.svg?style=flat-square)](https://packagist.org/packages/ensi/laravel-auditing)

Opiniated fork of [owen-it/laravel-auditing](https://github.com/owen-it/laravel-auditing)

## Installation

You can install the package via composer:

```bash
composer require ensi/laravel-auditing
```

Publish the migrations with:

```bash
php artisan vendor:publish --provider="Ensi\LaravelAuditing\LaravelAuditingServiceProvider"
```

### Migrate from 0.2.x to 0.3.0

1. Publish new migration `php artisan vendor:publish --provider="Ensi\LaravelAuditing\LaravelAuditingServiceProvider" --tag=migrations-0.3`
2. If the config `laravel-auditing.php` is published, then replace the `resolver.user`value with `Ensi\LaravelAuditing\Resolvers\UserResolver::class`

## Version Compatibility

| Laravel Auditing | Laravel                              | PHP  |
|------------------|--------------------------------------|------|
| ^0.1.2           | ^7.x \|\| ^8.x                       | ^8.0 |
| ^0.2.0           | ^7.x \|\| ^8.x                       | ^8.0 |
| ^0.3.0           | ^7.x \|\| ^8.x                       | ^8.0 |
| ^0.3.1           | ^8.x \|\| ^9.x                       | ^8.0 |
| ^0.3.5           | ^8.x \|\| ^9.x \|\| ^10.x \|\| ^11.x | ^8.0 |
| ^0.4.0           | ^9.x \|\| ^10.x \|\| ^11.x           | ^8.1 |

## Basic Usage

By default, no modification history is saved for models.
To enable logging for a specific model, you need to add the `Support s Audit` trait and the `Auditable` interface to it

```php
use Ensi\LaravelAuditing\Contracts\Auditable;
use Ensi\LaravelAuditing\SupportsAudit;

class Something extends Model implements Auditable {
    use SupportsAudit;
}

```

If we change the data of the child models from a logical point of view and want this change to take place under the parent model in the history, it is necessary to set the root entity (i.e. the model) in the transaction before changing the data.
This is done through the `Transaction` facade or the manager `\\Ensi\\LaravelAuditing\\Transactions\\ExtendedTransactionManager`

```php
DB::transaction(function () {
    Transaction::setRootEntity($rootModel);
    
    $relatedModel->save();
});
```

To add data to the history about who made the changes (a specific user, or, for example, a console command), again, you need to do this before changing the data, but through the `Subject` facade or the injection of `\\Ensi\\LaravelAuditing\\Resolvers\\SubjectManager`

```php
Subject::attach($subject); // $subject - an object implementing Ensi\LaravelAuditing\Contracts
```

The subject does not unbind after the transaction is completed.
It can be unlinked manually by calling the `Subject::detach()` method.

When processing http requests, you can set the subject in middleware. In console commands and handlers, event queues are reassigned during execution.

The subject can be any entity that supports the interface `\Ensi\LaravelAuditing\Contracts\Principal`.
If the subject is an ongoing task, for example, importing from a file, then it can return the ID of the user who created the task in the `getUserIdentifier()` method, and return the name of the imported file as the name.

In the user model, the `getAuthIdentifier()` and `getUserIdentifier()` methods return the same identifier.

Also, unlike the original package, not only the changed fields are saved in the history, but also the complete state of the model object at the time of the change.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

### Testing

1. composer install
2. composer test

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
