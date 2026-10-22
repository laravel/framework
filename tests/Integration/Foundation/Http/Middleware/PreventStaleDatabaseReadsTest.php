<?php

namespace Illuminate\Tests\Integration\Foundation\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventStaleDatabaseReads;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;

class PreventStaleDatabaseReadsTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'read' => ['database' => ':memory:'],
            'write' => ['database' => ':memory:'],
        ]);

        $app['config']->set('session.driver', 'array');
    }

    protected function defineRoutes($router)
    {
        $router->middleware([StartSession::class, PreventStaleDatabaseReads::class])->group(function ($router) {
            $router->get('read', fn () => DB::select('select 1'));
            $router->post('write', fn () => DB::statement('select 1'));
        });

        $router->middleware([StartSession::class, PreventStaleDatabaseReads::for(10)])->group(function ($router) {
            $router->get('read-later', fn () => DB::select('select 1'));
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeTime();
    }

    public function testReadsUseTheReadConnectionWithoutARecentWrite()
    {
        DB::enableQueryLog();

        $this->get('read')->assertOk();

        $this->assertSame('read', DB::getQueryLog()[0]['readWriteType']);
    }

    public function testReadsUseTheWriteConnectionAfterAWrite()
    {
        $this->post('write')->assertOk();

        DB::forgetRecordModificationState();
        DB::enableQueryLog();

        $this->travel(2)->seconds();

        $this->get('read')->assertOk();

        $this->assertSame('write', DB::getQueryLog()[0]['readWriteType']);
    }

    public function testReadsUseTheReadConnectionOnceTheDelayHasPassed()
    {
        $this->post('write')->assertOk();

        DB::forgetRecordModificationState();
        DB::enableQueryLog();

        $this->travel(3)->seconds();

        $this->get('read')->assertOk();

        $this->assertSame('read', DB::getQueryLog()[0]['readWriteType']);
    }

    public function testTheDelayCanBeCustomized()
    {
        $this->post('write')->assertOk();

        DB::forgetRecordModificationState();
        DB::enableQueryLog();

        $this->travel(10)->seconds();

        $this->get('read-later')->assertOk();

        $this->assertSame('write', DB::getQueryLog()[0]['readWriteType']);
    }

    public function testTheConnectionStopsBeingStickyOnceTheDelayHasPassed()
    {
        $this->post('write')->assertOk();

        DB::forgetRecordModificationState();

        $this->get('read')->assertOk();

        DB::enableQueryLog();

        $this->travel(3)->seconds();

        $this->get('read')->assertOk();

        $this->assertSame('read', DB::getQueryLog()[0]['readWriteType']);
    }
}
