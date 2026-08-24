<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $database = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? null);
        if ($database !== 'unifco_testing') {
            throw new \RuntimeException('Tests must run against the dedicated unifco_testing MySQL database.');
        }

        parent::setUp();
        if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'unifco_testing') {
            throw new \RuntimeException('Tests must run against the dedicated unifco_testing MySQL database.');
        }
    }
}
