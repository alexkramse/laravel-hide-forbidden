<?php

namespace Alexkramse\LaravelHideForbidden\Tests;

use Alexkramse\LaravelHideForbidden\HideForbiddenServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            HideForbiddenServiceProvider::class,
        ];
    }
}
