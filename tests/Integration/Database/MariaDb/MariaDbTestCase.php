<?php

namespace Illuminate\Tests\Integration\Database\MariaDb;

use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\RequiresDatabase;

#[RequiresDatabase('mariadb', default: true)]
abstract class MariaDbTestCase extends DatabaseTestCase
{
    //
}
