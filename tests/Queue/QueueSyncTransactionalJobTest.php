<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Queue\SyncQueue;
use PHPUnit\Framework\TestCase;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Contracts\Queue\Transactional;

class QueueTransactionalJobTest extends TestCase
{
    /**
     * @param $jobClassname
     * @param $isTransactional
     * @throws \ReflectionException
     *
     * @dataProvider jobsDataProvider
     */
    public function testRunFireInDatabaseTransaction($jobClassname, $isTransactional)
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($isTransactional ? $this->once(): $this->never())
            ->method('transaction')
            ->willReturnCallback(function ($handler) {
                $handler();
            });

        $db = $this->createMock(Manager::class);
        $db->expects($isTransactional ? $this->once(): $this->never())
            ->method('getConnection')
            ->willReturn($connection);

        /** @var Container|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->createMock(Container::class);
        $container->expects($this->exactly($isTransactional ? 2 : 1))
            ->method('make')
            ->willReturnCallback(function ($abstract) use ($db) {
                switch ($abstract) {
                    case 'db':
                        return $db;
                    default:
                        return new $abstract;
                }
            });

        $payloadFactory = new \ReflectionMethod(SyncQueue::class, 'createPayload');
        $payloadFactory->setAccessible(true);

        $payload = $payloadFactory->invoke(new SyncQueue(), $jobClassname, null, '');

        unset($_SERVER['0(*_*)0']);
        $job = new SyncJob($container, $payload, '', '');
        $job->fire();
        $this->assertEquals('0(*_*)0', $_SERVER['0(*_*)0']);
    }

    /**
     * @return array
     */
    public function jobsDataProvider(): array
    {
        return [
            [TransactionalJob::class, true],
            [SimpleJob::class, false],
        ];
    }
}

class SimpleJob
{
    public function fire()
    {
        $_SERVER['0(*_*)0'] = '0(*_*)0';
    }
}

class TransactionalJob extends SimpleJob implements Transactional
{

}
