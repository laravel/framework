<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Foundation\Events\DiscoverEvents;
use Mockery;
use Orchestra\Testbench\TestCase;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

class DiscoverEventsTest extends TestCase
{
    public function testDiscoverEventsClassFromFileWhenProjectDirectoryIsAppOnly(): void
    {
        $classFromFileMethod = (new ReflectionClass(DiscoverEvents::class))->getMethod('classFromFile');
        $classFromFileMethod->setAccessible(true);

        $splFileInfoMock = Mockery::mock(SplFileInfo::class);
        $splFileInfoMock->shouldReceive('getRealPath')
            ->once()
            ->withNoArgs()
            ->andReturn('/app/app/Listeners/FooListener');

        $class = $classFromFileMethod->invoke(null, $splFileInfoMock, '/app');

        $this->assertSame('App\\Listeners\\FooListener', $class);
    }
}
