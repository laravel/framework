<?php

namespace Illuminate\Tests\Session;

use BadMethodCallException;
use Illuminate\Contracts\Session\Session;
use Illuminate\Session\SymfonySessionDecorator;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SymfonySessionDecoratorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function test_it_implements_symfony_session_interface()
    {
        $store = m::mock(Session::class);
        $decorator = new SymfonySessionDecorator($store);

        $this->assertInstanceOf(SessionInterface::class, $decorator);
    }

    public function test_it_exposes_the_underlying_store()
    {
        $store = m::mock(Session::class);
        $decorator = new SymfonySessionDecorator($store);

        $this->assertSame($store, $decorator->store);
    }

    public function test_it_starts_the_session()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('start')->once()->andReturn(true);

        $decorator = new SymfonySessionDecorator($store);

        $this->assertTrue($decorator->start());
    }

    public function test_it_gets_the_session_id()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('getId')->once()->andReturn('session-id');

        $decorator = new SymfonySessionDecorator($store);

        $this->assertSame('session-id', $decorator->getId());
    }

    public function test_it_sets_the_session_id()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('setId')->once()->with('new-session-id');

        $decorator = new SymfonySessionDecorator($store);
        $decorator->setId('new-session-id');
    }

    public function test_it_gets_the_session_name()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('getName')->once()->andReturn('PHPSESSID');

        $decorator = new SymfonySessionDecorator($store);

        $this->assertSame('PHPSESSID', $decorator->getName());
    }

    public function test_it_sets_the_session_name()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('setName')->once()->with('custom_session');

        $decorator = new SymfonySessionDecorator($store);
        $decorator->setName('custom_session');
    }

    public function test_it_invalidates_the_session()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('invalidate')->once();

        $decorator = new SymfonySessionDecorator($store);

        $this->assertTrue($decorator->invalidate());
    }

    public function test_it_invalidates_the_session_with_lifetime()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('invalidate')->once();

        $decorator = new SymfonySessionDecorator($store);

        $this->assertTrue($decorator->invalidate(3600));
    }

    public function test_it_migrates_the_session()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('migrate')->once()->with(false);

        $decorator = new SymfonySessionDecorator($store);

        $this->assertTrue($decorator->migrate());
    }

    public function test_it_migrates_the_session_with_destroy()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('migrate')->once()->with(true);

        $decorator = new SymfonySessionDecorator($store);

        $this->assertTrue($decorator->migrate(true));
    }

    public function test_it_saves_the_session()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('save')->once();

        $decorator = new SymfonySessionDecorator($store);
        $decorator->save();
    }

    public function test_it_checks_if_session_has_attribute()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('has')->once()->with('foo')->andReturn(true);

        $decorator = new SymfonySessionDecorator($store);

        $this->assertTrue($decorator->has('foo'));
    }

    public function test_it_gets_attribute_from_session()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('get')->once()->with('foo', null)->andReturn('bar');

        $decorator = new SymfonySessionDecorator($store);

        $this->assertSame('bar', $decorator->get('foo'));
    }

    public function test_it_gets_attribute_with_default_value()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('get')->once()->with('foo', 'default')->andReturn('default');

        $decorator = new SymfonySessionDecorator($store);

        $this->assertSame('default', $decorator->get('foo', 'default'));
    }

    public function test_it_sets_attribute_in_session()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('put')->once()->with('foo', 'bar');

        $decorator = new SymfonySessionDecorator($store);
        $decorator->set('foo', 'bar');
    }

    public function test_it_gets_all_attributes()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('all')->once()->andReturn(['foo' => 'bar', 'baz' => 'qux']);

        $decorator = new SymfonySessionDecorator($store);

        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $decorator->all());
    }

    public function test_it_replaces_attributes()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('replace')->once()->with(['foo' => 'bar']);

        $decorator = new SymfonySessionDecorator($store);
        $decorator->replace(['foo' => 'bar']);
    }

    public function test_it_removes_attribute()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('remove')->once()->with('foo')->andReturn('bar');

        $decorator = new SymfonySessionDecorator($store);

        $this->assertSame('bar', $decorator->remove('foo'));
    }

    public function test_it_clears_all_attributes()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('flush')->once();

        $decorator = new SymfonySessionDecorator($store);
        $decorator->clear();
    }

    public function test_it_checks_if_session_is_started()
    {
        $store = m::mock(Session::class);
        $store->shouldReceive('isStarted')->once()->andReturn(true);

        $decorator = new SymfonySessionDecorator($store);

        $this->assertTrue($decorator->isStarted());
    }

    public function test_register_bag_throws_exception()
    {
        $store = m::mock(Session::class);
        $decorator = new SymfonySessionDecorator($store);
        $bag = m::mock(SessionBagInterface::class);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method not implemented by Laravel.');

        $decorator->registerBag($bag);
    }

    public function test_get_bag_throws_exception()
    {
        $store = m::mock(Session::class);
        $decorator = new SymfonySessionDecorator($store);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method not implemented by Laravel.');

        $decorator->getBag('attributes');
    }

    public function test_get_metadata_bag_throws_exception()
    {
        $store = m::mock(Session::class);
        $decorator = new SymfonySessionDecorator($store);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method not implemented by Laravel.');

        $decorator->getMetadataBag();
    }
}
