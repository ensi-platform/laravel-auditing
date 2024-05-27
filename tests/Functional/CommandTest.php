<?php

use Ensi\LaravelAuditing\Console\AuditDriverCommand;
use Ensi\LaravelAuditing\Tests\TestCase;
use Illuminate\Testing\PendingCommand;

use function Pest\Laravel\artisan;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertTrue;

uses(TestCase::class);

test('generate the audit driver success', function () {
    /** @var TestCase $this */

    assertInstanceOf(
        PendingCommand::class,
        artisan(AuditDriverCommand::class, ['name' => 'TestDriver'])
    );

    $driverFilePath = app_path('AuditDrivers/TestDriver.php');

    assertFileExists($driverFilePath);
    assertTrue(unlink($driverFilePath));
});
