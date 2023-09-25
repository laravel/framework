<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class EloquentTransactionWithAfterCommitUsingDatabaseTransactionsTest extends DatabaseTestCase
{
    use EloquentTransactionWithAfterCommitTests;
    use DatabaseTransactions;
}
