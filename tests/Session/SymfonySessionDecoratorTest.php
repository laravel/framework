<?php

namespace Illuminate\Tests\Session;

use Illuminate\Session\Store;
use Illuminate\Session\SymfonySessionDecorator;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use SessionHandlerInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

class SymfonySessionDecoratorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testRegisterBagInitializesWithExistingSessionData()
    {
        $handler = m::mock(SessionHandlerInterface::class);
        $handler->shouldReceive('read')->andReturn(serialize(['attributes' => ['key' => 'value']]));
        $store = new Store('session', $handler);
        $store->start();

        $decorator = new SymfonySessionDecorator($store);
        $bag = new AttributeBag('attributes');
        $decorator->registerBag($bag);

        $this->assertSame('value', $bag->get('key'));
    }

    public function testRegisterBagInitializesWithEmptyArrayWhenKeyNotInSession()
    {
        $handler = m::mock(SessionHandlerInterface::class);
        $handler->shouldReceive('read')->andReturn(serialize([]));
        $store = new Store('session', $handler);
        $store->start();

        $decorator = new SymfonySessionDecorator($store);
        $bag = new AttributeBag;
        $decorator->registerBag($bag);

        $this->assertSame([], $bag->all());
    }

    public function testGetBagReturnsRegisteredBag()
    {
        $handler = m::mock(SessionHandlerInterface::class);
        $handler->shouldReceive('read')->andReturn(serialize([]));
        $store = new Store('session', $handler);
        $store->start();

        $decorator = new SymfonySessionDecorator($store);
        $bag = new AttributeBag;
        $decorator->registerBag($bag);

        $this->assertSame($bag, $decorator->getBag($bag->getName()));
    }

    public function testGetBagThrowsForUnregisteredName()
    {
        $this->expectException(\InvalidArgumentException::class);

        $handler = m::mock(SessionHandlerInterface::class);
        $handler->shouldReceive('read')->andReturn(serialize([]));
        $store = new Store('session', $handler);

        $decorator = new SymfonySessionDecorator($store);
        $decorator->getBag('unknown');
    }

    public function testGetMetadataBagReturnsMetadataBagInstance()
    {
        $handler = m::mock(SessionHandlerInterface::class);
        $store = new Store('session', $handler);

        $decorator = new SymfonySessionDecorator($store);

        $this->assertInstanceOf(MetadataBag::class, $decorator->getMetadataBag());
    }

    public function testGetMetadataBagReturnsSameInstance()
    {
        $handler = m::mock(SessionHandlerInterface::class);
        $store = new Store('session', $handler);

        $decorator = new SymfonySessionDecorator($store);

        $this->assertSame($decorator->getMetadataBag(), $decorator->getMetadataBag());
    }

    public function testSaveSyncsBagDataBackToSession()
    {
        $handler = m::mock(SessionHandlerInterface::class);
        $handler->shouldReceive('read')->andReturn(serialize([]));
        $handler->shouldReceive('write')->once()->withArgs(function ($id, $data) {
            $unserialized = unserialize($data);

            return isset($unserialized['_sf2_attributes']) && $unserialized['_sf2_attributes'] === ['foo' => 'bar'];
        })->andReturn(true);

        $store = new Store('session', $handler);
        $store->start();

        $decorator = new SymfonySessionDecorator($store);
        $bag = new AttributeBag;
        $decorator->registerBag($bag);
        $bag->set('foo', 'bar');

        $decorator->save();
    }
}
