<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;

class EloquentTransactionWithAfterCommitUsingRefreshDatabaseTest extends DatabaseTestCase
{
    use EloquentTransactionWithAfterCommitTests;
    use RefreshDatabase;
}
