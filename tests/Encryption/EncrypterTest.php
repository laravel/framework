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


	public function testCanStillBeConstructedWithInvalidKeys()
	{
		$e = new Encrypter(''); // should not throw an exception

		$e = new Encrypter('YourSecretKey!!!'); // should not throw an exception
	}


	/**
	 * @expectedException Illuminate\Encryption\InvalidKeyException
	 * @expectedExceptionMessage The encryption key must not be empty.
	 */
	public function testEncryptWithEmptyStringAsKey()
	{
		$e = new Encrypter('');

		$e->encrypt('bar'); // throw the exception now that we tried to use the encrypter
	}


	/**
	 * @expectedException Illuminate\Encryption\InvalidKeyException
	 * @expectedExceptionMessage The encryption key must not be empty.
	 */
	public function testDecryptWithEmptyStringAsKey()
	{
		$e = new Encrypter('');

		$e->decrypt('bar'); // throw the exception now that we tried to use the encrypter
	}


	/**
	 * @expectedException Illuminate\Encryption\InvalidKeyException
	 * @expectedExceptionMessage The encryption key must be a random string.
	 */
	public function testEncryptWithDefaultStringAsKey()
	{
		$e = new Encrypter('YourSecretKey!!!');

		$e->encrypt('bar'); // throw the exception now that we tried to use the encrypter
	}


	/**
	 * @expectedException Illuminate\Encryption\InvalidKeyException
	 * @expectedExceptionMessage The encryption key must be a random string.
	 */
	public function testDecryptWithDefaultStringAsKey()
	{
		$e = new Encrypter('YourSecretKey!!!');

		$e->decrypt('bar'); // throw the exception now that we tried to use the encrypter
	}


	protected function getEncrypter()
	{
		return new Encrypter(str_repeat('a', 32));
	}

}
