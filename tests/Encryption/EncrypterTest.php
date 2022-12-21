<?php

namespace Illuminate\Tests\Encryption;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EncrypterTest extends TestCase
{
    public function testEncryption()
    {
        $e = new Encrypter(str_repeat('a', 16));
        $encrypted = $e->encrypt('foo');
        $this->assertNotSame('foo', $encrypted);
        $this->assertSame('foo', $e->decrypt($encrypted));
    }

    public function testRawStringEncryption()
    {
        $e = new Encrypter(str_repeat('a', 16));
        $encrypted = $e->encryptString('foo');
        $this->assertNotSame('foo', $encrypted);
        $this->assertSame('foo', $e->decryptString($encrypted));
    }

    public function testEncryptionUsingBase64EncodedKey()
    {
        $e = new Encrypter(random_bytes(16));
        $encrypted = $e->encrypt('foo');
        $this->assertNotSame('foo', $encrypted);
        $this->assertSame('foo', $e->decrypt($encrypted));
    }

    public function testEncryptedLengthIsFixed()
    {
        $e = new Encrypter(str_repeat('a', 16));
        $lengths = [];
        for ($i = 0; $i < 100; $i++) {
            $lengths[] = strlen($e->encrypt('foo'));
        }
        $this->assertSame(min($lengths), max($lengths));
    }

    public function testWithCustomCipher()
    {
        $e = new Encrypter(str_repeat('b', 32), 'AES-256-GCM');
        $encrypted = $e->encrypt('bar');
        $this->assertNotSame('bar', $encrypted);
        $this->assertSame('bar', $e->decrypt($encrypted));

        $e = new Encrypter(random_bytes(32), 'AES-256-GCM');
        $encrypted = $e->encrypt('foo');
        $this->assertNotSame('foo', $encrypted);
        $this->assertSame('foo', $e->decrypt($encrypted));
    }

    public function testCipherNamesCanBeMixedCase()
    {
        $upper = new Encrypter(str_repeat('b', 16), 'AES-128-GCM');
        $encrypted = $upper->encrypt('bar');
        $this->assertNotSame('bar', $encrypted);

        $lower = new Encrypter(str_repeat('b', 16), 'aes-128-gcm');
        $this->assertSame('bar', $lower->decrypt($encrypted));

        $mixed = new Encrypter(str_repeat('b', 16), 'aEs-128-GcM');
        $this->assertSame('bar', $mixed->decrypt($encrypted));
    }

    public function testThatAnAeadCipherIncludesTag()
    {
        $e = new Encrypter(str_repeat('b', 32), 'AES-256-GCM');
        $encrypted = $e->encrypt('foo');
        $data = json_decode(base64_decode($encrypted));

        $this->assertEmpty($data->mac);
        $this->assertNotEmpty($data->tag);
    }

    public function testThatAnAeadTagMustBeProvidedInFullLength()
    {
        $e = new Encrypter(str_repeat('b', 32), 'AES-256-GCM');
        $encrypted = $e->encrypt('foo');
        $data = json_decode(base64_decode($encrypted));

        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('Could not decrypt the data.');

        $data->tag = substr($data->tag, 0, 4);
        $encrypted = base64_encode(json_encode($data));
        $e->decrypt($encrypted);
    }

    public function testThatAnAeadTagCantBeModified()
    {
        $e = new Encrypter(str_repeat('b', 32), 'AES-256-GCM');
        $encrypted = $e->encrypt('foo');
        $data = json_decode(base64_decode($encrypted));

        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('Could not decrypt the data.');

        $data->tag[0] = $data->tag[0] === 'A' ? 'B' : 'A';
        $encrypted = base64_encode(json_encode($data));
        $e->decrypt($encrypted);
    }

    public function testThatANonAeadCipherIncludesMac()
    {
        $e = new Encrypter(str_repeat('b', 32), 'AES-256-CBC');
        $encrypted = $e->encrypt('foo');
        $data = json_decode(base64_decode($encrypted));

        $this->assertEmpty($data->tag);
        $this->assertNotEmpty($data->mac);
    }

    public function testDoNoAllowLongerKey()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported cipher or incorrect key length. Supported ciphers are: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm.');

        new Encrypter(str_repeat('z', 32));
    }

    public function testWithBadKeyLength()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported cipher or incorrect key length. Supported ciphers are: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm.');

        new Encrypter(str_repeat('a', 5));
    }

    public function testWithBadKeyLengthAlternativeCipher()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported cipher or incorrect key length. Supported ciphers are: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm.');

        new Encrypter(str_repeat('a', 16), 'AES-256-GCM');
    }

    public function testWithUnsupportedCipher()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported cipher or incorrect key length. Supported ciphers are: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm.');

        new Encrypter(str_repeat('c', 16), 'AES-256-CFB8');
    }

    public function testExceptionThrownWhenPayloadIsInvalid()
    {
        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('The payload is invalid.');

        $e = new Encrypter(str_repeat('a', 16));
        $payload = $e->encrypt('foo');
        $payload = str_shuffle($payload);
        $e->decrypt($payload);
    }

    public function testDecryptionExceptionIsThrownWhenUnexpectedTagIsAdded()
    {
        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('Unable to use tag because the cipher algorithm does not support AEAD.');

        $e = new Encrypter(str_repeat('a', 16));
        $payload = $e->encrypt('foo');
        $decodedPayload = json_decode(base64_decode($payload));
        $decodedPayload->tag = 'set-manually';
        $e->decrypt(base64_encode(json_encode($decodedPayload)));
    }

    public function testExceptionThrownWithDifferentKey()
    {
        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('The MAC is invalid.');

        $a = new Encrypter(str_repeat('a', 16));
        $b = new Encrypter(str_repeat('b', 16));
        $b->decrypt($a->encrypt('baz'));
    }

    public function testExceptionThrownWhenIvIsTooLong()
    {
        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('The payload is invalid.');

        $e = new Encrypter(str_repeat('a', 16));
        $payload = $e->encrypt('foo');
        $data = json_decode(base64_decode($payload), true);
        $data['iv'] .= $data['value'][0];
        $data['value'] = substr($data['value'], 1);
        $modified_payload = base64_encode(json_encode($data));
        $e->decrypt($modified_payload);
    }

    public function testSupportedMethodAcceptsAnyCasing()
    {
        $key = str_repeat('a', 16);

        $this->assertTrue(Encrypter::supported($key, 'AES-128-GCM'));
        $this->assertTrue(Encrypter::supported($key, 'aes-128-CBC'));
        $this->assertTrue(Encrypter::supported($key, 'aes-128-cbc'));
    }

    public function provideTamperedData()
    {
        $validIv = base64_encode(str_repeat('.', 16));

        return [
            [['iv' => ['value_in_array'], 'value' => '', 'mac' => '']],
            [['iv' => new class() {}, 'value' => '', 'mac' => '']],
            [['iv' => $validIv, 'value' => ['value_in_array'], 'mac' => '']],
            [['iv' => $validIv, 'value' => new class() {}, 'mac' => '']],
            [['iv' => $validIv, 'value' => '', 'mac' => ['value_in_array']]],
            [['iv' => $validIv, 'value' => '', 'mac' => null]],
            [['iv' => $validIv, 'value' => '', 'mac' => '', 'tag' => ['value_in_array']]],
            [['iv' => $validIv, 'value' => '', 'mac' => '', 'tag' => -1]],
        ];
    }

    /**
     * @dataProvider provideTamperedData
     */
    public function testTamperedPayloadWillGetRejected($payload)
    {
        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('The payload is invalid.');

        $enc = new Encrypter(str_repeat('x', 16));
        $enc->decrypt(base64_encode(json_encode($payload)));
    }
}
