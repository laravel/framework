<?php

namespace Illuminate\Foundation\Concerns;

use Closure;
use Illuminate\Container\Container;
use Mockery;
use Mockery\MockInterface;

trait Runnable
{
    /**
     * Resolve and run the runnable.
     */
    public static function run(mixed ...$arguments): mixed
    {
        return Container::getInstance()->make(static::class)->handle(...$arguments);
    }

    /**
     * Replace the runnable with a fake.
     *
     * @return \Mockery\MockInterface&static
     */
    public static function fake(mixed $result = null): MockInterface
    {
        $fake = Mockery::mock(static::class);

        $expectation = $fake->shouldReceive('handle');

        if ($result instanceof Closure) {
            $expectation->andReturnUsing($result);
        } elseif (func_num_args() > 0) {
            $expectation->andReturn($result);
        }

        Container::getInstance()->instance(static::class, $fake);

        return $fake;
    }
}
