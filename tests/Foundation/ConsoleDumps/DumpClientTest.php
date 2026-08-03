<?php

namespace Illuminate\Tests\Foundation\ConsoleDumps;

use Illuminate\Foundation\ConsoleDumps\DumpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class DumpClientTest extends TestCase
{
    public function test_it_sends_cloned_values_and_context_to_the_server()
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $host = stream_socket_get_name($server, false);

        $client = new DumpClient($host);
        $client->dump(['name' => 'Taylor'], [
            'source' => ['file' => '/app/routes/web.php', 'line' => 10],
        ], 'result');

        $connection = stream_socket_accept($server, 1);
        [$data, $context] = unserialize(base64_decode(fgets($connection)));

        $this->assertInstanceOf(Data::class, $data);
        $this->assertSame(['name' => 'Taylor'], $data->getValue(true));
        $this->assertSame('result', $data->getContext()['label']);
        $this->assertIsFloat($context['timestamp']);
        $this->assertSame(
            ['file' => '/app/routes/web.php', 'line' => 10],
            $context['source'],
        );

        fclose($connection);
        fclose($server);
    }

    public function test_it_silently_discards_dumps_when_the_server_is_unavailable()
    {
        $client = new DumpClient('tcp://127.0.0.1:1');

        $client->dump('value');

        $this->addToAssertionCount(1);
    }

    public function test_it_silently_discards_dumps_when_preparing_the_payload_fails()
    {
        $cloner = new class extends VarCloner
        {
            public function cloneVar(mixed $var, int $filter = 0): Data
            {
                throw new \RuntimeException('Unable to clone the value.');
            }
        };

        $client = new DumpClient(cloner: $cloner);

        $client->dump('value');

        $this->addToAssertionCount(1);
    }

    public function test_it_uses_a_non_blocking_socket()
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $host = stream_socket_get_name($server, false);
        $client = new TestableDumpClient($host);

        $this->assertTrue($client->connectForTesting());
        $this->assertFalse(stream_get_meta_data($client->socketForTesting())['blocked']);

        fclose($server);
    }
}

class TestableDumpClient extends DumpClient
{
    public function connectForTesting(): bool
    {
        return $this->connect();
    }

    /**
     * @return resource|null
     */
    public function socketForTesting()
    {
        return $this->socket;
    }
}
