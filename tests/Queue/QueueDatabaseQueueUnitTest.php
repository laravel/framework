<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Database\Connection;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\Queue;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class QueueDatabaseQueueUnitTest extends TestCase
{
    use VerifiesDoubles;

    public function testFailureToCreatePayloadFromObject()
    {
        $this->expectException('InvalidArgumentException');

        $job = new stdClass;
        $job->invalid = "\xc3\x28";

        $queue = new DatabaseQueue($this->connection(), 'table', 'default');
        $class = new ReflectionClass(Queue::class);

        $createPayload = $class->getMethod('createPayload');
        $createPayload->invokeArgs($queue, [
            $job,
            'queue-name',
        ]);
    }

    public function testFailureToCreatePayloadFromArray()
    {
        $this->expectException('InvalidArgumentException');

        $queue = new DatabaseQueue($this->connection(), 'table', 'default');
        $class = new ReflectionClass(Queue::class);

        $createPayload = $class->getMethod('createPayload');
        $createPayload->invokeArgs($queue, [
            ["\xc3\x28"],
            'queue-name',
        ]);
    }

    public function testBuildDatabaseRecordWithPayloadAtTheEnd()
    {
        $queue = new DatabaseQueue($this->connection(), 'table', 'default');
        $class = new ReflectionClass(DatabaseQueue::class);
        $record = $class->getMethod('buildDatabaseRecord')->invoke($queue, 'queue', 'any_payload', 0);
        $this->assertArrayHasKey('payload', $record);
        $this->assertArrayHasKey('payload', array_slice($record, -1, 1, true));
    }

    public function testGetLockForPoppingIsCached()
    {
        $database = Double::for(Connection::class);
        $queue = new DatabaseQueue($database, 'table', 'default');

        $pdo = Double::for(\PDO::class);
        $pdo->expects('getAttribute')->with(\PDO::ATTR_DRIVER_NAME)->returns('mysql');
        $pdo->expects('getAttribute')->with(\PDO::ATTR_SERVER_VERSION)->returns('8.0.36');

        $database->expects('getPdo')->times(2)->returns($pdo);
        $database->expects('getConfig')->with('version')->returns(null);

        $method = new \ReflectionMethod($queue, 'getLockForPopping');

        $result1 = $method->invoke($queue);
        $result2 = $method->invoke($queue);

        $this->assertSame('FOR UPDATE SKIP LOCKED', $result1);
        $this->assertSame($result1, $result2);
    }

    protected function connection(): Connection
    {
        return new Connection(new \PDO('sqlite::memory:'));
    }
}
