<?php

namespace Illuminate\Tests\Integration\Session;

use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Carbon;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration('session')]
class DatabaseSessionHandlerTest extends DatabaseTestCase
{
    public function test_basic_read_write_functionality()
    {
        $connection = $this->app['db']->connection();
        $handler = new DatabaseSessionHandler($connection, 'sessions', 1);
        $handler->setContainer($this->app);

        // read non-existing session id:
        $this->assertEquals('', $handler->read('invalid_session_id'));

        // open and close:
        $this->assertTrue($handler->open('', ''));
        $this->assertTrue($handler->close());

        // write and read:
        $this->assertTrue($handler->write('valid_session_id_2425', json_encode(['foo' => 'bar'])));
        $this->assertEquals(['foo' => 'bar'], json_decode($handler->read('valid_session_id_2425'), true));
        $this->assertEquals(1, $connection->table('sessions')->count());

        $session = $connection->table('sessions')->first();
        $this->assertNotNull($session->user_agent);
        $this->assertNotNull($session->ip_address);

        // re-write and read:
        $this->assertTrue($handler->write('valid_session_id_2425', json_encode(['over' => 'ride'])));
        $this->assertEquals(['over' => 'ride'], json_decode($handler->read('valid_session_id_2425'), true));
        $this->assertEquals(1, $connection->table('sessions')->count());

        // handler object writes only one session id:
        $this->assertTrue($handler->write('other_id', 'data'));
        $this->assertEquals(1, $connection->table('sessions')->count());

        $handler->setExists(false);
        $this->assertTrue($handler->write('other_id', 'data'));
        $this->assertEquals(2, $connection->table('sessions')->count());

        // read expired:
        Carbon::setTestNow(Carbon::now()->addMinutes(2));
        $this->assertEquals('', $handler->read('valid_session_id_2425'));

        // rewriting an expired session-id, makes it live:
        $this->assertTrue($handler->write('valid_session_id_2425', json_encode(['come' => 'alive'])));
        $this->assertEquals(['come' => 'alive'], json_decode($handler->read('valid_session_id_2425'), true));
    }

    public function test_garbage_collector()
    {
        $connection = $this->app['db']->connection();

        $handler = new DatabaseSessionHandler($connection, 'sessions', 1, $this->app);
        $handler->write('simple_id_1', 'abcd');
        $this->assertEquals(0, $handler->gc(1));

        Carbon::setTestNow(Carbon::now()->addSeconds(2));

        $handler = new DatabaseSessionHandler($connection, 'sessions', 1, $this->app);
        $handler->write('simple_id_2', 'abcd');
        $this->assertEquals(1, $handler->gc(2));
        $this->assertEquals(1, $connection->table('sessions')->count());

        Carbon::setTestNow(Carbon::now()->addSeconds(2));

        $this->assertEquals(1, $handler->gc(1));
        $this->assertEquals(0, $connection->table('sessions')->count());
    }

    public function test_destroy()
    {
        $connection = $this->app['db']->connection();
        $handler1 = new DatabaseSessionHandler($connection, 'sessions', 1, $this->app);
        $handler2 = clone $handler1;

        $handler1->write('id_1', 'some data');
        $handler2->write('id_2', 'some data');

        // destroy invalid session-id:
        $this->assertEquals(true, $handler1->destroy('invalid_session_id'));
        // nothing deleted:
        $this->assertEquals(2, $connection->table('sessions')->count());

        // destroy valid session-id:
        $this->assertEquals(true, $handler2->destroy('id_1'));
        // only one row is deleted:
        $this->assertEquals(1, $connection->table('sessions')->where('id', 'id_2')->count());
    }

    public function test_it_can_work_without_container()
    {
        $connection = $this->app['db']->connection();
        $handler = new DatabaseSessionHandler($connection, 'sessions', 1);

        // write and read:
        $this->assertTrue($handler->write('session_id', 'some data'));
        $this->assertEquals('some data', $handler->read('session_id'));
        $this->assertEquals(1, $connection->table('sessions')->count());

        $session = $connection->table('sessions')->first();
        $this->assertNull($session->user_agent);
        $this->assertNull($session->ip_address);
        $this->assertNull($session->user_id);
    }
}
