<?php

namespace Illuminate\Tests\Integration\Foundation;

use Orchestra\Testbench\TestCase;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\ConnectionResolverInterface;

class CoreContainerAliasesTest extends TestCase
{
    public function testItCanResolveCoreContainerAliases()
    {
        $this->assertInstanceOf(DatabaseManager::class, $this->app->make(ConnectionResolverInterface::class));
    }
}
