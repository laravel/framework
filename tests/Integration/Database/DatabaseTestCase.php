<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\TestCase;

class DatabaseTestCase extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');
    }
}
