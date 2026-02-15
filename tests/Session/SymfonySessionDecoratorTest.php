<?php

namespace Illuminate\Tests\Session;

use BadMethodCallException;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Session\SymfonySessionDecorator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;

class SymfonySessionDecoratorTest extends TestCase
{
    public function test_it_delegates_basic_session_operations()
    {
        $decorator = new SymfonySessionDecorator($this->makeStore());

        $this->assertFalse($decorator->isStarted());
        $this->assertTrue($decorator->start());
        $this->assertTrue($decorator->isStarted());

        $this->assertSame('name', $decorator->getName());
        $decorator->setName('other');
        $this->assertSame('other', $decorator->getName());

        $sessionId = $decorator->getId();
        $decorator->setId($sessionId);
        $this->assertSame($sessionId, $decorator->getId());

        $this->assertFalse($decorator->has('foo'));
        $decorator->set('foo', 'bar');
        $this->assertTrue($decorator->has('foo'));
        $this->assertSame('bar', $decorator->get('foo'));
        $all = $decorator->all();
        $this->assertArrayHasKey('_token', $all);
        $this->assertSame('bar', $all['foo']);

        $decorator->replace(['baz' => 'qux']);
        $this->assertTrue($decorator->has('foo'));
        $this->assertSame('qux', $decorator->get('baz'));
        $this->assertSame('qux', $decorator->remove('baz'));
        $this->assertFalse($decorator->has('baz'));

        $decorator->set('alpha', 'beta');
        $decorator->clear();
        $this->assertSame([], $decorator->all());
    }

    public function test_register_bag_throws_exception()
    {
        $decorator = new SymfonySessionDecorator($this->makeStore());

        $this->expectException(BadMethodCallException::class);

        $decorator->registerBag($this->makeBag());
    }

    public function test_get_bag_throws_exception()
    {
        $decorator = new SymfonySessionDecorator($this->makeStore());

        $this->expectException(BadMethodCallException::class);

        $decorator->getBag('foo');
    }

    public function test_get_metadata_bag_throws_exception()
    {
        $decorator = new SymfonySessionDecorator($this->makeStore());

        $this->expectException(BadMethodCallException::class);

        $decorator->getMetadataBag();
    }

    private function makeStore(): Store
    {
        return new Store(
            'name',
            new ArraySessionHandler(10),
            'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'
        );
    }

    private function makeBag(): SessionBagInterface
    {
        return new class implements SessionBagInterface
        {
            public function getName(): string
            {
                return 'bag';
            }

            public function initialize(array &$array): void
            {
            }

            public function getStorageKey(): string
            {
                return '_bag';
            }

            public function clear(): array
            {
                return [];
            }
        };
    }
}
