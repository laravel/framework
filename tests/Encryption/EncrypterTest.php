<?php

use Illuminate\Encryption\Encrypter;

class EncrypterTest extends PHPUnit_Framework_TestCase
{
    public function testEncryption()
    {
        $e = $this->getEncrypter();
        $this->assertNotEquals('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', $e->encrypt('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'));
        $encrypted = $e->encrypt('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        $this->assertEquals('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', $e->decrypt($encrypted));
    }

    public function testEncryptionWithCustomCipher()
    {
        $e = $this->getEncrypter();
        $e->setCipher('AES-256-CBC');
        $this->assertNotEquals('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', $e->encrypt('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'));
        $encrypted = $e->encrypt('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        $this->assertEquals('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', $e->decrypt($encrypted));
    }

    /**
     * @expectedException Illuminate\Contracts\Encryption\DecryptException
     */
    public function testExceptionThrownWhenPayloadIsInvalid()
    {
        $e = $this->getEncrypter();
        $payload = $e->encrypt('foo');
        $payload = str_shuffle($payload);
        $e->decrypt($payload);
    }

    protected function getEncrypter()
    {
        return new Encrypter(str_repeat('a', 32));
    }
}
