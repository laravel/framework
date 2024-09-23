<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\RequiresDatabase;

#[RequiresDatabase('mysql', default: true)]
abstract class MySqlTestCase extends DatabaseTestCase
{
    //
}
