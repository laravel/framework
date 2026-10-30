<?php

namespace Illuminate\Tests\Foundation\Cloud;

use Illuminate\Foundation\Cloud\SocketHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[RequiresOperatingSystem('Linux')]
class SocketHandlerTest extends TestCase
{
    protected string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir().'/'.uniqid('cloud-log-').'.sock';
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
    }

    public function test_it_reconnects_after_a_write_times_out()
    {
        $server = stream_socket_server('unix://'.$this->path);
        $handler = new SocketHandler('unix://'.$this->path, persistent: true, timeout: 0.1);
        $logger = new Logger('cloud', [$handler]);
        $first = $second = null;

        try {
            $this->assertIsResource($server);

            $exception = null;

            try {
                $logger->info(str_repeat('a', 1024 * 1024));
            } catch (RuntimeException $e) {
                $exception = $e;
            }

            $this->assertNotNull($exception, 'The write should time out while nothing reads the socket.');
            $this->assertSame('Write timed-out', $exception->getMessage());

            $first = stream_socket_accept($server, 1);
            $this->assertIsResource($first);
            stream_set_blocking($first, false);

            while (fread($first, 65536)) {
                //
            }

            $logger->info('recovered');

            $second = stream_socket_accept($server, 1);
            $this->assertIsResource($second);
            stream_set_timeout($second, 1);

            $this->assertStringContainsString('recovered', fgets($second));
        } finally {
            $handler->closeSocket();

            foreach ([$first, $second, $server] as $socket) {
                if (is_resource($socket)) {
                    fclose($socket);
                }
            }
        }
    }
}
