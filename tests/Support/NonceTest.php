<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Nonce;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class NonceTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Nonce::reset();
    }

    public function testSetStringNonce()
    {
        $nonce = 'foo-bar';
        Nonce::setNonce($nonce);

        $this->assertEquals(Nonce::getNonce(), $nonce);
    }

    public function testSetCallableReturnsString()
    {
        $nonce = 'foo-bar';

        Nonce::setNonce(function () use ($nonce) {
            return $nonce;
        });

        $this->assertEquals(Nonce::getNonce(), $nonce);
    }

    public function testSetCallableReturnsObject()
    {
        $this->expectException(RuntimeException::class);

        Nonce::setNonce(function () {
            return new \StdClass;
        });

        Nonce::getNonce();
    }

    public function testSetCallableReturnsEmptyString()
    {
        $this->expectException(RuntimeException::class);

        Nonce::setNonce(function () {
            return '';
        });

        Nonce::getNonce();
    }

    public function testGetNonceWithoutSettingAnything()
    {
        $this->assertNull(Nonce::getNonce());
    }
}
