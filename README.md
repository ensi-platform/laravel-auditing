# Laravel Ensi Filesystem

Пакет для работы с историей изменений данных Ensi в Laravel приложениях.
Основан на owen-it/laravel-auditing.

## Установка

1. Добавьте в composer.json в repositories 

```
repositories: [
    {
        "type": "vcs",
        "url": "https://gitlab.com/greensight/ensi/packages/laravel-ensi-audit.git"
    }
],

```

2. `composer require ensi/laravel-ensi-audit`
3. `php artisan vendor:publish --provider="Ensi\LaravelEnsiAudit\EnsiAuditServiceProvider"`
