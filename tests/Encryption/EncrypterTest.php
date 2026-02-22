<?php

namespace Illuminate\Tests\Encryption;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EncrypterTest extends TestCase
{
    public function testEncryption(): void
    {
        $e = new Encrypter(str_repeat('a', 16));
        $encrypted = $e->encrypt('foo');
        $this->assertNotSame('foo', $encrypted);
        $this->assertSame('foo', $e->decrypt($encrypted));

        $encrypted = $e->encrypt('');
        $this->assertSame('', $e->decrypt($encrypted));

        $longString = str_repeat('a', 1000);
        $encrypted = $e->encrypt($longString);
        $this->assertSame($longString, $e->decrypt($encrypted));

        $data = ['foo' => 'bar', 'baz' => 'qux'];
        $encryptedArray = $e->encrypt($data);
        $this->assertNotSame($data, $encryptedArray);
        $this->assertSame($data, $e->decrypt($encryptedArray));
    }

    public function testRawStringEncryption()
    {
        $e = new Encrypter(str_repeat('a', 16));
        $encrypted = $e->encryptString('foo');
        $this->assertNotSame('foo', $encrypted);
        $this->assertSame('foo', $e->decryptString($encrypted));
    }

    public function testRawStringEncryptionWithPreviousKeys()
    {
        $previous = new Encrypter(str_repeat('b', 16));
        $previousValue = $previous->encryptString('foo');

        $new = new Encrypter(str_repeat('a', 16));
        $new->previousKeys([str_repeat('b', 16)]);

        $decrypted = $new->decryptString($previousValue);
        $this->assertSame('foo', $decrypted);
    }

    public function testItValidatesMacOnPerKeyBasis()
    {
        // Payload created with (key: str_repeat('b', 16)) but will
        // "successfully" decrypt with (key: str_repeat('a', 16)), however it
        // outputs a random binary string as it is not the correct key.
        $encrypted = 'eyJpdiI6Ilg0dFM5TVRibEFqZW54c3lQdWJoVVE9PSIsInZhbHVlIjoiRGJpa2p2ZHI3eUs0dUtRakJneUhUUT09IiwibWFjIjoiMjBjZWYxODdhNThhOTk4MTk1NTc0YTE1MDgzODU1OWE0ZmQ4MDc5ZjMxYThkOGM1ZmM1MzlmYzBkYTBjMWI1ZiIsInRhZyI6IiJ9';

        $new = new Encrypter(str_repeat('a', 16));
        $new->previousKeys([str_repeat('b', 16)]);
        $this->assertSame('foo', $new->decryptString($encrypted));
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

    public static function provideTamperedData()
    {
        $validIv = base64_encode(str_repeat('.', 16));

        return [
            [['iv' => ['value_in_array'], 'value' => '', 'mac' => '']],
            [['iv' => new class() {
            }, 'value' => '', 'mac' => '']],
            [['iv' => $validIv, 'value' => ['value_in_array'], 'mac' => '']],
            [['iv' => $validIv, 'value' => new class() {
            }, 'mac' => '']],
            [['iv' => $validIv, 'value' => '', 'mac' => ['value_in_array']]],
            [['iv' => $validIv, 'value' => '', 'mac' => null]],
            [['iv' => $validIv, 'value' => '', 'mac' => '', 'tag' => ['value_in_array']]],
            [['iv' => $validIv, 'value' => '', 'mac' => '', 'tag' => -1]],
        ];
    }

    #[DataProvider('provideTamperedData')]
    public function testTamperedPayloadWillGetRejected($payload)
    {
        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('The payload is invalid.');

        $enc = new Encrypter(str_repeat('x', 16));
        $enc->decrypt(base64_encode(json_encode($payload)));
    }

    public function testEncryptedReturnsTrueForEncryptedValue()
    {
        $e = new Encrypter(str_repeat('a', 16));
        $encrypted = $e->encrypt('foo');

        $this->assertTrue(Encrypter::appearsEncrypted($encrypted));
    }

    public function testEncryptedReturnsTrueForEncryptedArray()
    {
        $e = new Encrypter(str_repeat('a', 16));
        $encrypted = $e->encrypt(['foo' => 'bar']);

        $this->assertTrue(Encrypter::appearsEncrypted($encrypted));
    }

    public function testEncryptedReturnsFalseForPlainText()
    {
        $this->assertFalse(Encrypter::appearsEncrypted('foo'));
        $this->assertFalse(Encrypter::appearsEncrypted('APP_NAME=Laravel'));
        $this->assertFalse(Encrypter::appearsEncrypted("APP_NAME=Laravel\nAPP_ENV=local"));
    }

    public function testEncryptedReturnsFalseForNonString()
    {
        $this->assertFalse(Encrypter::appearsEncrypted(123));
        $this->assertFalse(Encrypter::appearsEncrypted(['foo' => 'bar']));
        $this->assertFalse(Encrypter::appearsEncrypted(null));
    }

    public function testGenerateKeyCreatesCorrectLengthKey()
    {
        $key128cbc = Encrypter::generateKey('aes-128-cbc');
        $this->assertSame(16, mb_strlen($key128cbc, '8bit'));

        $key256cbc = Encrypter::generateKey('aes-256-cbc');
        $this->assertSame(32, mb_strlen($key256cbc, '8bit'));

        $key128gcm = Encrypter::generateKey('AES-128-GCM');
        $this->assertSame(16, mb_strlen($key128gcm, '8bit'));

        $key256gcm = Encrypter::generateKey('AES-256-GCM');
        $this->assertSame(32, mb_strlen($key256gcm, '8bit'));
    }

    public function testGenerateKeyCreatesUsableKey()
    {
        $key = Encrypter::generateKey('aes-256-gcm');
        $e = new Encrypter($key, 'aes-256-gcm');

        $encrypted = $e->encrypt('test value');
        $this->assertSame('test value', $e->decrypt($encrypted));
    }


    public function testGetKeyReturnsCurrentKey()
    {
        $key = str_repeat('a', 16);
        $e = new Encrypter($key);

        $this->assertSame($key, $e->getKey());
    }

    public function testGetAllKeysReturnsCurrentAndPreviousKeys()
    {
        $currentKey = str_repeat('a', 16);
        $previousKey1 = str_repeat('b', 16);
        $previousKey2 = str_repeat('c', 16);

        $e = new Encrypter($currentKey);
        $e->previousKeys([$previousKey1, $previousKey2]);

        $allKeys = $e->getAllKeys();
        $this->assertCount(3, $allKeys);
        $this->assertSame($currentKey, $allKeys[0]);
        $this->assertSame($previousKey1, $allKeys[1]);
        $this->assertSame($previousKey2, $allKeys[2]);
    }

    public function testGetPreviousKeysReturnsOnlyPreviousKeys()
    {
        $currentKey = str_repeat('a', 16);
        $previousKey = str_repeat('b', 16);

        $e = new Encrypter($currentKey);
        $this->assertEmpty($e->getPreviousKeys());

        $e->previousKeys([$previousKey]);
        $this->assertSame([$previousKey], $e->getPreviousKeys());
    }

    public function testPreviousKeysThrowsExceptionForInvalidKey()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported cipher or incorrect key length. Supported ciphers are: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm.');

        $e = new Encrypter(str_repeat('a', 16));
        $e->previousKeys([str_repeat('b', 5)]); // Invalid key length
    }

    public function testPreviousKeysMethodReturnsSelf()
    {
        $e = new Encrypter(str_repeat('a', 16));
        $result = $e->previousKeys([str_repeat('b', 16)]);

        $this->assertSame($e, $result);
    }
}
