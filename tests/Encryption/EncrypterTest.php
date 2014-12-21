<?php

use Illuminate\Encryption\Encrypter;

class EncrypterTest extends PHPUnit_Framework_TestCase {

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
		$e->setCipher(MCRYPT_RIJNDAEL_256);
		$this->assertNotEquals('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', $e->encrypt('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'));
		$encrypted = $e->encrypt('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
		$this->assertEquals('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', $e->decrypt($encrypted));
	}

	/**
	 * @expectedException Illuminate\Encryption\DecryptException
	 * @expectedExceptionMessage Invalid data.
	 */
	public function testExceptionThrownWhenPayloadIsInvalid()
	{
		$e = $this->getEncrypter();
		$payload = $e->encrypt('foo');
		$payload = str_shuffle($payload);
		$e->decrypt($payload);
	}


	/**
	 * @expectedException Illuminate\Encryption\InvalidKeyException
	 * @expectedExceptionMessage The encryption key must be a string.
	 */
	public function testConstuctWithNonString()
	{
		return new Encrypter(123);
	}


	/**
	 * @expectedException Illuminate\Encryption\InvalidKeyException
	 * @expectedExceptionMessage The encryption key must be not be empty.
	 */
	public function testConstuctWithEmptyString()
	{
		return new Encrypter('');
	}


	/**
	 * @expectedException Illuminate\Encryption\InvalidKeyException
	 * @expectedExceptionMessage The encryption key must be a random string.
	 */
	public function testConstuctWithDefaultString()
	{
		return new Encrypter('YourSecretKey!!!');
	}


	protected function getEncrypter()
	{
		return new Encrypter(str_repeat('a', 32));
	}

}
