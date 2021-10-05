<?php

namespace Ensi\LaravelAuditing\Tests\Functional;

use Ensi\LaravelAuditing\Tests\AuditingTestCase;
use Illuminate\Testing\PendingCommand;

class CommandTest extends AuditingTestCase
{
    /**
     * @test
     */
    public function itWillGenerateTheAuditDriver()
    {
        $driverFilePath = sprintf(
            '%s/AuditDrivers/TestDriver.php',
            $this->app->path()
        );

        $this->assertInstanceOf(
            PendingCommand::class,
            $this->artisan(
                'auditing:audit-driver',
                [
                    'name' => 'TestDriver',
                ]
            )
        );

        $this->assertFileExists($driverFilePath);

        $this->assertTrue(unlink($driverFilePath));
    }
}
