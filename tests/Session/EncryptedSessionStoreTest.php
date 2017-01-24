<?php

namespace Illuminate\Tests\Session;

use Mockery as m;
use ReflectionClass;
use PHPUnit\Framework\TestCase;

class EncryptedSessionStoreTest extends TestCase
{
    public function tearDown()
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
        $reflection = new ReflectionClass('Illuminate\Session\EncryptedStore');

        return $reflection->newInstanceArgs($this->getMocks());
    }

    public function getMocks()
    {
        return [
            $this->getSessionName(),
            m::mock('SessionHandlerInterface'),
            m::mock('Illuminate\Contracts\Encryption\Encrypter'),
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
