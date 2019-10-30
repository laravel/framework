<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\DatabaseManager;
use Orchestra\Testbench\TestCase;

class CoreContainerAliasesTest extends TestCase
{
    public function testItCanResolveCoreContainerAliases()
    {
        $this->assertInstanceOf(DatabaseManager::class, $this->app->make(ConnectionResolverInterface::class));
    }
}
