<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\RequiresDatabase;

#[RequiresDatabase('sqlsrv')]
abstract class SqlServerTestCase extends DatabaseTestCase
{
    //
}
