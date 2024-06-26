<?php

namespace Ensi\LaravelAuditing\Tests\Unit;

use Ensi\LaravelAuditing\AuditableObserver;
use Ensi\LaravelAuditing\Tests\Data\Models\Article;
use Ensi\LaravelAuditing\Tests\TestCase;

use function PHPUnit\Framework\assertSame;

uses(TestCase::class);

test('it executes the auditor successfully', function (string $eventMethod, bool $expectedBefore, bool $expectedAfter) {
    /** @var TestCase $this */

    $observer = new AuditableObserver();
    $model = Article::factory()->create();

    assertSame($expectedBefore, $observer::$restoring);

    $observer->$eventMethod($model);

    assertSame($expectedAfter, $observer::$restoring);
})->with([
    ['retrieved', false, false],
    ['created', false, false],
    ['updated', false, false],
    ['deleted', false, false],
    ['restoring', false, true],
    ['restored', true, false],
]);
