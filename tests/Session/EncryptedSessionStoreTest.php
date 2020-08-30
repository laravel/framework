<?php

namespace Illuminate\Tests\Session;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Session\EncryptedStore;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SessionHandlerInterface;

class EncryptedSessionStoreTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testSessionIsProperlyEncrypted()
    {
        $session = $this->getSession();
        $session->getEncrypter()->shouldReceive('decrypt')->once()->with(serialize([]))->andReturn(serialize([]));
        $session->getHandler()->shouldReceive('read')->once()->andReturn(serialize([]));
        $session->start();
        $session->put('foo', 'bar');
        $session->flash('baz', 'boom');
        $session->now('qux', 'norf');
        $serialized = serialize([
            '_token' => $session->token(),
            'foo' => 'bar',
            'baz' => 'boom',
            '_flash' => [
                'new' => [],
                'old' => ['baz'],
            ],
        ]);
        $session->getEncrypter()->shouldReceive('encrypt')->once()->with($serialized)->andReturn($serialized);
        $session->getHandler()->shouldReceive('write')->once()->with(
            $this->getSessionId(),
            $serialized
        );
        $session->save();

        $this->assertFalse($session->isStarted());
    }

    public function getSession()
    {
        $reflection = new ReflectionClass(EncryptedStore::class);

        return $reflection->newInstanceArgs($this->getMocks());
    }

    public function getMocks()
    {
        return [
            $this->getSessionName(),
            m::mock(SessionHandlerInterface::class),
            m::mock(Encrypter::class),
            $this->getSessionId(),
        ];
    }

    public function getSessionId()
    {
        return 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    }

    public function getSessionName()
    {
        return 'name';
    }
}
