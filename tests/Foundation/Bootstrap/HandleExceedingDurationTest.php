<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use Carbon\CarbonInterval;
use Illuminate\Foundation\Application;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class HandleExceedingDurationTest extends TestCase
{
    public function testItCanHandleExceedingHttpKernelBootstrappingDuration()
    {
        $app = new Application();
        $app->instance('called', false);
        $app->instance('bootstrapper', new class () {
            public function bootstrap($app)
            {
                $app->whenBootstrappingLongerThan(CarbonInterval::seconds(1), function () use ($app) {
                    $app->instance('called', true);
                });

                Carbon::setTestNow(Carbon::now()->addSeconds(1)->addMilliseconds());
            }
        });

        Carbon::setTestNow(Carbon::now());
        $app->bootstrapWith(['bootstrapper']);

        $this->assertTrue($app['called']);
    }

    public function testItDoesntCallWhenExactlyThresholdDuration()
    {
        $app = new Application();
        $app->instance('called', false);
        $app->instance('bootstrapper', new class () {
            public function bootstrap($app)
            {
                $app->whenBootstrappingLongerThan(CarbonInterval::seconds(1), function () use ($app) {
                    $app->instance('called', true);
                });

                Carbon::setTestNow(Carbon::now()->addSeconds(1));
            }
        });

        Carbon::setTestNow(Carbon::now());
        $app->bootstrapWith(['bootstrapper']);

        $this->assertFalse($app['called']);
    }

    public function testItCanExceedDurationWhenSpecifyingDurationAsDateTime()
    {
        $app = new Application();
        $app->instance('called', false);
        $app->instance('bootstrapper', new class () {
            public function bootstrap($app)
            {
                $app->whenBootstrappingLongerThan(Carbon::now()->addSeconds(1), function () use ($app) {
                    $app->instance('called', true);
                });

                Carbon::setTestNow(Carbon::now()->addSeconds(1)->addMilliseconds(1));
            }
        });

        Carbon::setTestNow(Carbon::now());
        $app->bootstrapWith(['bootstrapper']);

        $this->assertTrue($app['called']);
    }

    public function testItCanStayUnderDurationWhenSpecifyingDurationAsDateTime()
    {
        $app = new Application();
        $app->instance('called', false);
        $app->instance('bootstrapper', new class () {
            public function bootstrap($app)
            {
                $app->whenBootstrappingLongerThan(Carbon::now()->addSeconds(1), function () use ($app) {
                    $app->instance('called', true);
                });

                Carbon::setTestNow(Carbon::now()->addSeconds(1));
            }
        });

        Carbon::setTestNow(Carbon::now());
        $app->bootstrapWith(['bootstrapper']);

        $this->assertFalse($app['called']);
    }

    public function testItCanExceedThresholdWhenSpecifyingDurationAsMilliseconds()
    {
        $app = new Application();
        $app->instance('called', false);
        $app->instance('bootstrapper', new class () {
            public function bootstrap($app)
            {
                $app->whenBootstrappingLongerThan(1000, function () use ($app) {
                    $app->instance('called', true);
                });

                Carbon::setTestNow(Carbon::now()->addSeconds(1)->addMilliseconds(1));
            }
        });

        Carbon::setTestNow(Carbon::now());
        $app->bootstrapWith(['bootstrapper']);

        $this->assertTrue($app['called']);
    }

    public function testItCanStayUnderThresholdWhenSpecifyingDurationAsMilliseconds()
    {
        $app = new Application();
        $app->instance('called', false);
        $app->instance('bootstrapper', new class () {
            public function bootstrap($app)
            {
                $app->whenBootstrappingLongerThan(1000, function () use ($app) {
                    $app->instance('called', true);
                });

                Carbon::setTestNow(Carbon::now()->addSeconds(1));
            }
        });

        Carbon::setTestNow(Carbon::now());
        $app->bootstrapWith(['bootstrapper']);

        $this->assertFalse($app['called']);
    }

    public function testItClearsStartTimeAfterBootstrapping()
    {
        $app = new Application();
        $app->instance('bootstrapper', new class () {
            public function bootstrap($app)
            {
                $app->whenBootstrappingLongerThan(1000, function () use ($app) {
                    $app->instance('called', true);
                });

                Carbon::setTestNow(Carbon::now()->addSeconds(1));
            }
        });

        $this->assertNull($app->bootstrappingStartedAt());
        $app->bootstrapWith([]);
        $this->assertNull($app->bootstrappingStartedAt());
    }
}
