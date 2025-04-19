<?php

namespace Illuminate\Tests\Integration\Session;

use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Carbon;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class DatabaseSessionHandlerTest extends DatabaseTestCase
{
    public function testBasicFunctionality()
    {
        $path = __DIR__.'/../../../src/Illuminate/Session/Console/stubs/database.stub';
        $migration = require_once $path;

        $migration->up();

        $handler = new DatabaseSessionHandler($this->app['db']->connection('mysql'), 'sessions', 1, $this->app);

        $handler->write('valid_session_id_2425', json_encode(['foo' => 'bar']));

        $this->assertEquals(['foo' => 'bar'], json_decode($handler->read('valid_session_id_2425')));
        $this->assertEquals('', $handler->read('invalid_session_id_2425'));

        $this->assertEquals(true, $handler->destroy('invalid_session_id_2425'));
        $this->assertEquals('', $handler->read('valid_session_id_2425'));
        $this->assertEquals(0, $handler->gc(1));
        Carbon::setTestNow(Carbon::now()->addSeconds(2));
        $this->assertEquals(1, $handler->gc(1));
        $migration->down();
        $this->assertTrue(false);
    }
}
