<?php

namespace Illuminate\Tests\Foundation\ConsoleDumps;

use Illuminate\Foundation\ConsoleDumps\DumpClient;
use Illuminate\Foundation\ConsoleDumps\DumpServer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DumpServerTest extends TestCase
{
    public function test_it_normalizes_the_server_host()
    {
        $server = new DumpServer('127.0.0.1:9912');

        $this->assertSame('tcp://127.0.0.1:9912', $server->getHost());
    }

    public function test_it_receives_dumps_from_the_client()
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('The PCNTL extension is required.');
        }

        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $host = stream_socket_get_name($socket, false);
        fclose($socket);

        $server = new DumpServer($host);
        $server->start();

        $processId = pcntl_fork();

        if ($processId === -1) {
            $this->fail('Unable to fork the dump client process.');
        }

        if ($processId === 0) {
            (new DumpClient($host))->dump('value', ['test' => true]);

            exit(0);
        }

        $received = null;

        try {
            $server->listen(function ($data, $context) use (&$received) {
                $received = [$data->getValue(true), $context];

                throw new StopListeningException;
            });
        } catch (StopListeningException) {
            // The dump was received...
        } finally {
            pcntl_waitpid($processId, $status);
        }

        $this->assertSame('value', $received[0]);
        $this->assertTrue($received[1]['test']);
        $this->assertIsFloat($received[1]['timestamp']);
    }
}

class StopListeningException extends RuntimeException
{
    //
}
