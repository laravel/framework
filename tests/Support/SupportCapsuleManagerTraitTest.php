<?php

namespace Illuminate\Tests\Support;

use Mockery as m;
use Illuminate\Support\Fluent;
use PHPUnit\Framework\TestCase;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Traits\CapsuleManagerTrait;

class SupportCapsuleManagerTraitTest extends TestCase
{
    use CapsuleManagerTrait;

    protected function tearDown(): void
    {
        m::close();
    }

    public function testSetupContainerForCapsule()
    {
        $this->container = null;
        $app = new Container;

        $this->assertNull($this->setupContainer($app));
        $this->assertEquals($app, $this->getContainer());
        $this->assertInstanceOf(Fluent::class, $app['config']);
    }

    public function testSetupContainerForCapsuleWhenConfigIsBound()
    {
        $this->container = null;
        $app = new Container;
        $app['config'] = m::mock(Repository::class);

        $this->assertNull($this->setupContainer($app));
        $this->assertEquals($app, $this->getContainer());
        $this->assertInstanceOf(Repository::class, $app['config']);
    }
}
