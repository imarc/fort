<?php

declare(strict_types=1);

namespace Imarc\Fort\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
