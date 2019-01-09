<?php

namespace Illuminate\Tests\Encryption;

use PHPUnit\Framework\TestCase;
use Illuminate\Encryption\OpenSSLEncrypter;

class OpenSSLEncrypterTest extends TestCase
{
    public function testEncryption()
    {
        $e = new OpenSSLEncrypter(str_repeat('a', 16));
        $encrypted = $e->encrypt('foo');
        $this->assertNotEquals('foo', $encrypted);
        $this->assertEquals('foo', $e->decrypt($encrypted));
    }

    public function testRawStringEncryption()
    {
        $e = new OpenSSLEncrypter(str_repeat('a', 16));
        $encrypted = $e->encrypt('foo', false);
        $this->assertNotEquals('foo', $encrypted);
        $this->assertEquals('foo', $e->decrypt($encrypted, false));
    }

    public function testEncryptionUsingBase64EncodedKey()
    {
        $e = new OpenSSLEncrypter(random_bytes(16));
        $encrypted = $e->encrypt('foo');
        $this->assertNotEquals('foo', $encrypted);
        $this->assertEquals('foo', $e->decrypt($encrypted));
    }

    public function testWithCustomCipher()
    {
        $e = new OpenSSLEncrypter(str_repeat('b', 32), OpenSSLEncrypter::AES_256);
        $encrypted = $e->encrypt('bar');
        $this->assertNotEquals('bar', $encrypted);
        $this->assertEquals('bar', $e->decrypt($encrypted));

        $e = new OpenSSLEncrypter(random_bytes(32), OpenSSLEncrypter::AES_256);
        $encrypted = $e->encrypt('foo');
        $this->assertNotEquals('foo', $encrypted);
        $this->assertEquals('foo', $e->decrypt($encrypted));
    }

    public function testGenerateKey()
    {
        $e = new OpenSSLEncrypter('');
        $f = new OpenSSLEncrypter($e->generateKey());
        $f->encrypt('bar');
    }

    public function testGenerateKeyWithCustomCipher()
    {
        $e = new OpenSSLEncrypter('');
        $f = new OpenSSLEncrypter($e->generateKey(OpenSSLEncrypter::AES_256), OpenSSLEncrypter::AES_256);
        $f->encrypt('bar');
    }

    /**
     * @expectedException \Illuminate\Contracts\Encryption\EncryptException
     * @expectedExceptionMessage The only supported ciphers are AES-128-CBC and AES-256-CBC with the correct key lengths.
     */
    public function testDoNoAllowLongerKey()
    {
        $e = new OpenSSLEncrypter(str_repeat('z', 32));
        $e->encrypt('bar');
    }

    /**
     * @expectedException \Illuminate\Contracts\Encryption\EncryptException
     * @expectedExceptionMessage The only supported ciphers are AES-128-CBC and AES-256-CBC with the correct key lengths.
     */
    public function testWithBadKeyLength()
    {
        $e = new OpenSSLEncrypter(str_repeat('a', 5));
        $e->encrypt('bar');
    }

    /**
     * @expectedException \Illuminate\Contracts\Encryption\EncryptException
     * @expectedExceptionMessage The only supported ciphers are AES-128-CBC and AES-256-CBC with the correct key lengths.
     */
    public function testWithBadKeyLengthAlternativeCipher()
    {
        $e = new OpenSSLEncrypter(str_repeat('a', 16), 'AES-256-CFB8');
        $e->encrypt('bar');
    }

    /**
     * @expectedException \Illuminate\Contracts\Encryption\EncryptException
     * @expectedExceptionMessage The only supported ciphers are AES-128-CBC and AES-256-CBC with the correct key lengths.
     */
    public function testWithUnsupportedCipher()
    {
        $e = new OpenSSLEncrypter(str_repeat('c', 16), 'AES-256-CFB8');
        $e->encrypt('bar');
    }

    /**
     * @expectedException \Illuminate\Contracts\Encryption\DecryptException
     * @expectedExceptionMessage The payload is invalid.
     */
    public function testExceptionThrownWhenPayloadIsInvalid()
    {
        $e = new OpenSSLEncrypter(str_repeat('a', 16));
        $payload = $e->encrypt('foo');
        $payload = str_shuffle($payload);
        $e->decrypt($payload);
    }

    /**
     * @expectedException \Illuminate\Contracts\Encryption\DecryptException
     * @expectedExceptionMessage The MAC is invalid.
     */
    public function testExceptionThrownWithDifferentKey()
    {
        $a = new OpenSSLEncrypter(str_repeat('a', 16));
        $b = new OpenSSLEncrypter(str_repeat('b', 16));
        $b->decrypt($a->encrypt('baz'));
    }

    /**
     * @expectedException \Illuminate\Contracts\Encryption\DecryptException
     * @expectedExceptionMessage The payload is invalid.
     */
    public function testExceptionThrownWhenIvIsTooLong()
    {
        $e = new OpenSSLEncrypter(str_repeat('a', 16));
        $payload = $e->encrypt('foo');
        $data = json_decode(base64_decode($payload), true);
        $data['iv'] .= $data['value'][0];
        $data['value'] = substr($data['value'], 1);
        $modified_payload = base64_encode(json_encode($data));
        $e->decrypt($modified_payload);
    }
}
