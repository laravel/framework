<?php

namespace Illuminate\Tests\Testing;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Foundation\Testing\TestCase;
use Orchestra\Testbench\Concerns\CreatesApplication;

class PendingTransactionTesting extends TestCase
{
    use CreatesApplication;

    public function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('database.default', 'testing');    
    }

    public function testItThrowsAnExceptionIfPendingTransaction()
    {
        \DB::beginTransaction();
    }

    public function testItDoesntThrowAnExceptionIfTransactionClosed()
    {
        \DB::beginTransaction();
        \DB::beginTransaction();
        \DB::rollback();
        \DB::rollback();
    }
}
