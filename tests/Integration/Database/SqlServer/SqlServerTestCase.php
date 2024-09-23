<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Tests\Integration\Database\DatabaseTestCase;

#[RequiresDatabase('sqlsrv', default: true)]
abstract class SqlServerTestCase extends DatabaseTestCase
{
    //
}
