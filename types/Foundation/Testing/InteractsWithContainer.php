<?php

namespace Illuminate\Types\Foundation\Testing;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithContainer;
use Mockery\MockInterface;
use User;

use function PHPStan\Testing\assertType;

class InteractsWithContainerTestCase
{
    use InteractsWithContainer;

    protected Application $app;

    public function test(): void
    {
        assertType('Mockery\MockInterface&User', $this->mock(User::class));
        assertType('Mockery\MockInterface&User', $this->mock(User::class, function ($mock) {}));

        assertType('Mockery\MockInterface&User', $this->partialMock(User::class));
        assertType('Mockery\MockInterface&User', $this->partialMock(User::class, function ($mock) {}));

        assertType('Mockery\MockInterface&User', $this->spy(User::class));
        assertType('Mockery\MockInterface&User', $this->spy(User::class, function ($mock) {}));

        assertType('Mockery\MockInterface', $this->mock('my.service'));
        assertType('Mockery\MockInterface', $this->partialMock('my.service'));
        assertType('Mockery\MockInterface', $this->spy('my.service'));
    }
}
