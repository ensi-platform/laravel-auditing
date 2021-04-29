# Laravel Ensi Audit

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
4. Добавьте в `config/app` класс провайдера `Ensi\LaravelEnsiAudit\EnsiAuditServiceProvider::class`

## Использование

Чтобы данные аудита писались для сущности при ее изменении, надо добавить в ее модель trait
SupportsAudit.

```
class Something extends Model {
    use SupportsAudit;
}

```

В случае, если требуется записи аудита связанных сущностей или объектов значений привязать к
корневой сущности, необходимо в транзакции задать корневую сущность, до того как будут
записываться/удаляться связанные.

```
DB::transaction(function () {
    Transaction::setRootEntity($rootModel);
    
    $relatedModel->save();
});
```

Для добавления в аудит текущего субъекта (пользователя, выполняемого задания) нужно перед
записью модели задать модель субъекта.

```
Subject::attach($subject);
```
Субъект не привязан к транзакции. Его можно отвязать вызовом метода `detach()`.
При обработке http запросов, можно задавать субъекта через middleware. В консольных командах и
обработчиках очереди событий переназначать в процессе выполнения.

Субъектом может являться любая сущность, поддерживающая интерфейс `\Ensi\LaravelEnsiAudit\Contracts\Principal`.
Если субъектом является выполняемое задание, например, импорт из файла, то оно может возвращать идентификатор
пользователя, создавшего задание в методе `getUserIdentifier()`, а в качестве наименования возвращать имя
импортируемого файла.

В модели пользователя методы `getAuthIdentifier()` и `getUserIdentifier()` возвращают один и тот же идентификатор.
