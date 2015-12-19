<?php

use Illuminate\Encryption\Encrypter;

class EncrypterTest extends PHPUnit_Framework_TestCase
{
    public function testEncryption()
    {
        $e = new Encrypter(str_repeat('a', 16));
        $encrypted = $e->encrypt('foo');
        $this->assertNotEquals('foo', $encrypted);
        $this->assertEquals('foo', $e->decrypt($encrypted));
    }

    public function testWithCustomCipher()
    {
        $e = new Encrypter(str_repeat('b', 32), 'AES-256-CBC');
        $encrypted = $e->encrypt('bar');
        $this->assertNotEquals('bar', $encrypted);
        $this->assertEquals('bar', $e->decrypt($encrypted));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The only supported ciphers are AES-128-CBC and AES-256-CBC with the correct key lengths.
     */
    public function testDoNoAllowLongerKey()
    {
        new Encrypter(str_repeat('z', 32));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The only supported ciphers are AES-128-CBC and AES-256-CBC with the correct key lengths.
     */
    public function testWithBadKeyLength()
    {
        new Encrypter(str_repeat('a', 5));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The only supported ciphers are AES-128-CBC and AES-256-CBC with the correct key lengths.
     */
    public function testWithBadKeyLengthAlternativeCipher()
    {
        new Encrypter(str_repeat('a', 16), 'AES-256-CFB8');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The only supported ciphers are AES-128-CBC and AES-256-CBC with the correct key lengths.
     */
    public function testWithUnsupportedCipher()
    {
        new Encrypter(str_repeat('c', 16), 'AES-256-CFB8');
    }

    /**
     * @expectedException Illuminate\Contracts\Encryption\DecryptException
     * @expectedExceptionMessage The payload is invalid.
     */
    public function testExceptionThrownWhenPayloadIsInvalid()
    {
        $e = new Encrypter(str_repeat('a', 16));
        $payload = $e->encrypt('foo');
        $payload = str_shuffle($payload);
        $e->decrypt($payload);
    }

    /**
     * @expectedException Illuminate\Contracts\Encryption\DecryptException
     * @expectedExceptionMessage The MAC is invalid.
     */
    public function testExceptionThrownWithDifferentKey()
    {
        $a = new Encrypter(str_repeat('a', 16));
        $b = new Encrypter(str_repeat('b', 16));
        $b->decrypt($a->encrypt('baz'));
    }
}
