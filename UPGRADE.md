# Upgrades

## 0.2.x to 0.3.0

1. Publish new migration `php artisan vendor:publish --provider="Ensi\LaravelAuditing\LaravelAuditingServiceProvider" --tag=migrations-0.3`
2. If the config `laravel-auditing.php` is published, then replace the `resolver.user`value with `Ensi\LaravelAuditing\Resolvers\UserResolver::class`
