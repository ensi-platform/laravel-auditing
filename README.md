# Laravel Ensi Audit

Пакет для работы с историей изменений данных Ensi в Laravel приложениях.
Является форком [owen-it/laravel-auditing](https://github.com/owen-it/laravel-auditing).

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

По-умолчанию никакая история изменения для моделей не сохраняется.
Чтобы включить логирование для конкретной модели надо добавить ей трейт `SupportsAudit`.

```php
use Ensi\LaravelEnsiAudit\SupportsAudit;

class Something extends Model {
    use SupportsAudit;
}

```

В случае, если мы меняем данные дочерних с логической точки зрения моделей и хотим чтобы в истории это изменение проходило под родительской моделью, необходимо в транзакции до изменения данных задать корневую сущность (т.е модель).
Делается это через фасад `Transaction` или менеджер `\\Ensi\\LaravelEnsiAudit\\Transactions\\ExtendedTransactionManager` 

```php
DB::transaction(function () {
    Transaction::setRootEntity($rootModel);
    
    $relatedModel->save();
});
```

Для добавления в историю данных о том кто произвел изменения (конкретный пользователь, или, например, консольная команда) опять же нужно это сделать до изменения данных, но уже через фасад `Subject` или инъекцию `\\Ensi\\LaravelEnsiAudit\\Resolvers\\SubjectManager`

```php
Subject::attach($subject); // $subject - объект реализующий Ensi\LaravelEnsiAudit\Contracts
```

Субъект не отвязывается после завершения транзакции. 
Его можно отвязать вручную вызовом метода `Subject::detach()`.

При обработке http запросов, можно задавать субъекта в middleware. В консольных командах и
обработчиках очереди событий переназначать в процессе выполнения.

Субъектом может являться любая сущность, поддерживающая интерфейс `\Ensi\LaravelEnsiAudit\Contracts\Principal`.
Если субъектом является выполняемое задание, например, импорт из файла, то оно может возвращать идентификатор
пользователя, создавшего задание в методе `getUserIdentifier()`, а в качестве наименования возвращать имя
импортируемого файла.

В модели пользователя методы `getAuthIdentifier()` и `getUserIdentifier()` возвращают один и тот же идентификатор.

Также в отличии от исходного пакета в истории сохраняются не только измененные поля, но и полное состояние объекта модели на момент изменения.
