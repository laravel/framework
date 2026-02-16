<?php

namespace Illuminate\Tests\Integration\Foundation;

use Exception;
use Illuminate\Cache\ArrayStore;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use function report;

class PendingReportTest extends TestCase
{
    protected Container $app;
    protected Factory $cache;
    protected ExceptionHandler $handler;
    protected Repository $store;

    protected function setUp(): void
    {
        $this->app = Container::getInstance();
        $this->app->instance(ContainerContract::class, $this->app);
        $this->app->instance(Factory::class, $this->cache = m::mock(Factory::class));
        $this->app->instance(Repository::class, $this->store = new \Illuminate\Cache\Repository(new ArrayStore(false)));
        $this->app->instance(ExceptionHandler::class, $this->handler = m::mock(ExceptionHandler::class));
    }

    protected function tearDown(): void
    {
        Container::setInstance();
        m::close();
    }

    public function test_reports_immediately(): void
    {
        $e = new Exception('test');

        $this->handler->expects('report')->with($e)->once();

        report($e);
    }

    public function test_reports_immediately_using_string(): void
    {
        $this->handler->expects('report')->withArgs(function (Exception $e) {
            return $e->getMessage() === 'test';
        })->once();

        report('test');
    }

    public function test_reports_once_every_minute(): void
    {
        $e = new Exception('test');

        $this->cache->expects('store')->with(null)->twice()->andReturn($this->store);
        $this->handler->expects('report')->with($e)->once();

        report()->exception($e)->every(60);
        report()->exception($e)->every(60);
    }

    public function test_reports_up_to_three_times_every_ten_minutes(): void
    {
        $e = new Exception('test');

        $this->cache->expects('store')->with(null)->times(4)->andReturn($this->store);
        $this->handler->expects('report')->with($e)->times(3);

        report()->exception($e)->atMost(3)->every(60 * 10);
        report()->exception($e)->atMost(3)->every(60 * 10);
        report()->exception($e)->atMost(3)->every(60 * 10);
        report()->exception($e)->atMost(3)->every(60 * 10);
    }

    public function test_reports_using_custom_store(): void
    {
        $e = new Exception('test');

        $this->cache->expects('store')->with('custom')->twice()->andReturn($this->store);
        $this->handler->expects('report')->with($e)->once();

        report()->exception($e)->using('custom')->every(60);
        report()->exception($e)->using('custom')->every(60);
    }
}
