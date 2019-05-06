<?php

namespace Illuminate\Tests\Foundation;

use Mockery;
use ReflectionClass;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Finder\SplFileInfo;
use Illuminate\Foundation\Events\DiscoverEvents;

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
