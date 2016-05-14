<?php

use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\WorkCommand;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\RedisQueue;
use Illuminate\Queue\Worker;
use Illuminate\Redis\Database;
use Mockery\MockInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class QueueWorkCommandTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var MockInterface|QueueManager
     */
    private $manager;

    /**
     * @var RedisQueue
     */
    private $queue;

    /**
     * @var Database
     */
    private $redis;

    /**
     * @var WorkCommand
     */
    private $workCommand;

    public function setUp()
    {
        parent::setUp();

        $laravel = new Application();

        $crypt = new Encrypter('someRandomString');

        $laravel->instance('encrypter', $crypt);

        $this->redis = new Database([
            'cluster' => false,
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 5,
            ],
        ]);

        $this->redis->connection()->flushdb();

        $this->queue = new RedisQueue($this->redis);

        $this->queue->setEncrypter($crypt);

        $this->queue->setContainer($laravel);

        $this->manager = Mockery::mock(QueueManager::class);

        $worker = new Worker($this->manager);

        $this->workCommand = new WorkCommand($worker);

        $this->workCommand->setLaravel($laravel);
    }

    public function tearDown()
    {
        parent::tearDown();
        Mockery::close();
        $this->redis->connection()->flushdb();
    }

    /**
     * @expectedException \Illuminate\Queue\TimeoutException
     */
    public function testTimeoutWhenWorkingOnAnInfinitLoopJob()
    {
        $this->queue->push(function () {
            while (true) {
                sleep(10);
            }
        });

        $this->manager->shouldReceive('connection')->once()->with(null)->andReturn($this->queue);
        $this->manager->shouldReceive('getName')->once()->with(null)->andReturn('default');
        $this->workCommand->run(new ArrayInput([
            '--timeout' => 2,
        ]), new NullOutput());
    }

    public function testNoTimeoutWhenWorkingTheJobIsFastEnough()
    {
        $this->queue->push(function () {
            //do nothing!
        });

        $this->manager->shouldReceive('connection')->once()->with(null)->andReturn($this->queue);
        $this->manager->shouldReceive('getName')->once()->with(null)->andReturn('default');
        $this->workCommand->run(new ArrayInput([
            '--timeout' => 200,
        ]), new NullOutput());
    }
}
