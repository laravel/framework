<?php

namespace Illuminate\Tests\Config\Attributes;

use Illuminate\Config\Attributes\InjectFromConfig;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class InjectFromConfigTest extends TestCase
{
    public function testItReturnsDefaultWhenConfigNotBound()
    {
        $attribute = new InjectFromConfig('app.name', 'default');

        $container = m::mock(Container::class);
        $container->shouldReceive('bound')->with('config')->once()->andReturnFalse();

        $this->assertSame('default', $attribute->resolve($container));
    }

    public function testItGetsFromConfig()
    {
        $attribute = new InjectFromConfig('app.name', 'default');

        $config = m::mock(Repository::class);
        $config->shouldReceive('get')->with('app.name', 'default')->once()->andReturn('Laravel');

        $container = m::mock(Container::class);
        $container->shouldReceive('bound')->with('config')->once()->andReturnTrue();
        $container->shouldReceive('make')->with('config')->once()->andReturn($config);

        $this->assertSame('Laravel', $attribute->resolve($container));
    }
}
