<?php

namespace Illuminate\Tests\Encryption;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EncrypterTest extends TestCase
{
    public function test_encryption(): void
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

    public function test_raw_string_encryption()
    {
        $e = new Encrypter(str_repeat('a', 16));
        $encrypted = $e->encryptString('foo');
        $this->assertNotSame('foo', $encrypted);
        $this->assertSame('foo', $e->decryptString($encrypted));
    }

    public function test_raw_string_encryption_with_previous_keys()
    {
        $previous = new Encrypter(str_repeat('b', 16));
        $previousValue = $previous->encryptString('foo');

        $new = new Encrypter(str_repeat('a', 16));
        $new->previousKeys([str_repeat('b', 16)]);

        $decrypted = $new->decryptString($previousValue);
        $this->assertSame('foo', $decrypted);
    }

    public function test_it_validates_mac_on_per_key_basis()
    {
        // Payload created with (key: str_repeat('b', 16)) but will
        // "successfully" decrypt with (key: str_repeat('a', 16)), however it
        // outputs a random binary string as it is not the correct key.
        $encrypted = 'eyJpdiI6Ilg0dFM5TVRibEFqZW54c3lQdWJoVVE9PSIsInZhbHVlIjoiRGJpa2p2ZHI3eUs0dUtRakJneUhUUT09IiwibWFjIjoiMjBjZWYxODdhNThhOTk4MTk1NTc0YTE1MDgzODU1OWE0ZmQ4MDc5ZjMxYThkOGM1ZmM1MzlmYzBkYTBjMWI1ZiIsInRhZyI6IiJ9';

        $new = new Encrypter(str_repeat('a', 16));
        $new->previousKeys([str_repeat('b', 16)]);
        $this->assertSame('foo', $new->decryptString($encrypted));
    }

    public function test_encryption_using_base64_encoded_key()
    {
        $e = new Encrypter(random_bytes(16));
        $encrypted = $e->encrypt('foo');
        $this->assertNotSame('foo', $encrypted);
        $this->assertSame('foo', $e->decrypt($encrypted));
    }

    public function test_encrypted_length_is_fixed()
    {
        $e = new Encrypter(str_repeat('a', 16));
        $lengths = [];
        for ($i = 0; $i < 100; $i++) {
            $lengths[] = strlen($e->encrypt('foo'));
        }
        $this->assertSame(min($lengths), max($lengths));
    }

    public function test_with_custom_cipher()
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

    public function test_cipher_names_can_be_mixed_case()
    {
        $upper = new Encrypter(str_repeat('b', 16), 'AES-128-GCM');
        $encrypted = $upper->encrypt('bar');
        $this->assertNotSame('bar', $encrypted);

        $lower = new Encrypter(str_repeat('b', 16), 'aes-128-gcm');
        $this->assertSame('bar', $lower->decrypt($encrypted));

        $mixed = new Encrypter(str_repeat('b', 16), 'aEs-128-GcM');
        $this->assertSame('bar', $mixed->decrypt($encrypted));
    }

    public function test_that_an_aead_cipher_includes_tag()
    {
        $e = new Encrypter(str_repeat('b', 32), 'AES-256-GCM');
        $encrypted = $e->encrypt('foo');
        $data = json_decode(base64_decode($encrypted));

        $this->assertEmpty($data->mac);
        $this->assertNotEmpty($data->tag);
    }

    public function test_that_an_aead_tag_must_be_provided_in_full_length()
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

    public function test_that_an_aead_tag_cant_be_modified()
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

    public function test_that_a_non_aead_cipher_includes_mac()
    {
        $e = new Encrypter(str_repeat('b', 32), 'AES-256-CBC');
        $encrypted = $e->encrypt('foo');
        $data = json_decode(base64_decode($encrypted));

        $this->assertEmpty($data->tag);
        $this->assertNotEmpty($data->mac);
    }

    public function test_do_no_allow_longer_key()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported cipher or incorrect key length. Supported ciphers are: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm.');

        new Encrypter(str_repeat('z', 32));
    }

    public function test_with_bad_key_length()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported cipher or incorrect key length. Supported ciphers are: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm.');

        new Encrypter(str_repeat('a', 5));
    }

    public function test_with_bad_key_length_alternative_cipher()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported cipher or incorrect key length. Supported ciphers are: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm.');

        new Encrypter(str_repeat('a', 16), 'AES-256-GCM');
    }

    public function test_with_unsupported_cipher()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported cipher or incorrect key length. Supported ciphers are: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm.');

        new Encrypter(str_repeat('c', 16), 'AES-256-CFB8');
    }

    public function test_exception_thrown_when_payload_is_invalid()
    {
        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('The payload is invalid.');

        $e = new Encrypter(str_repeat('a', 16));
        $payload = $e->encrypt('foo');
        $payload = str_shuffle($payload);
        $e->decrypt($payload);
    }

    public function test_decryption_exception_is_thrown_when_unexpected_tag_is_added()
    {
        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('Unable to use tag because the cipher algorithm does not support AEAD.');

        $e = new Encrypter(str_repeat('a', 16));
        $payload = $e->encrypt('foo');
        $decodedPayload = json_decode(base64_decode($payload));
        $decodedPayload->tag = 'set-manually';
        $e->decrypt(base64_encode(json_encode($decodedPayload)));
    }

    public function test_exception_thrown_with_different_key()
    {
        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('The MAC is invalid.');

        $a = new Encrypter(str_repeat('a', 16));
        $b = new Encrypter(str_repeat('b', 16));
        $b->decrypt($a->encrypt('baz'));
    }

    public function test_exception_thrown_when_iv_is_too_long()
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

    public function test_supported_method_accepts_any_casing()
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
            [['iv' => new class {}, 'value' => '', 'mac' => '']],
            [['iv' => $validIv, 'value' => ['value_in_array'], 'mac' => '']],
            [['iv' => $validIv, 'value' => new class {}, 'mac' => '']],
            [['iv' => $validIv, 'value' => '', 'mac' => ['value_in_array']]],
            [['iv' => $validIv, 'value' => '', 'mac' => null]],
            [['iv' => $validIv, 'value' => '', 'mac' => '', 'tag' => ['value_in_array']]],
            [['iv' => $validIv, 'value' => '', 'mac' => '', 'tag' => -1]],
        ];
    }

    #[DataProvider('provideTamperedData')]
    public function test_tampered_payload_will_get_rejected($payload)
    {
        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('The payload is invalid.');

        $enc = new Encrypter(str_repeat('x', 16));
        $enc->decrypt(base64_encode(json_encode($payload)));
    }

    public function test_deterministic_encryption(): void
    {
        $e = new Encrypter(str_repeat('a', 16));
        $value = 'test data';
        // Test that deterministic encryption produces the same result for the same input
        $encrypted1 = $e->encrypt($value, true, true);
        $encrypted2 = $e->encrypt($value, true, true);
        $this->assertSame($encrypted1, $encrypted2);
        // Test that we can still decrypt deterministic encryption
        $this->assertSame($value, $e->decrypt($encrypted1));
        $this->assertSame($value, $e->decrypt($encrypted2));
        // Test with array data
        $arrayData = ['key' => 'value', 'number' => 42];
        $encryptedArray1 = $e->encrypt($arrayData, true, true);
        $encryptedArray2 = $e->encrypt($arrayData, true, true);
        $this->assertSame($encryptedArray1, $encryptedArray2);
        $this->assertSame($arrayData, $e->decrypt($encryptedArray1));
        // Test that different values produce different encrypted results
        $differentValue = 'different test data';
        $encryptedDifferent = $e->encrypt($differentValue, true, true);
        $this->assertNotSame($encrypted1, $encryptedDifferent);
        $this->assertSame($differentValue, $e->decrypt($encryptedDifferent));
    }

    public function test_deterministic_string_encryption(): void
    {
        $e = new Encrypter(str_repeat('a', 16));
        $value = 'test string';
        // Test that deterministic string encryption produces the same result
        $encrypted1 = $e->encryptString($value, true);
        $encrypted2 = $e->encryptString($value, true);
        $this->assertSame($encrypted1, $encrypted2);
        // Test that we can decrypt deterministic string encryption
        $this->assertSame($value, $e->decryptString($encrypted1));
        $this->assertSame($value, $e->decryptString($encrypted2));

        // Test empty string
        $emptyEncrypted1 = $e->encryptString('', true);
        $emptyEncrypted2 = $e->encryptString('', true);
        $this->assertSame($emptyEncrypted1, $emptyEncrypted2);
        $this->assertSame('', $e->decryptString($emptyEncrypted1));

        // Test long string
        $longString = str_repeat('deterministic', 100);
        $longEncrypted1 = $e->encryptString($longString, true);
        $longEncrypted2 = $e->encryptString($longString, true);
        $this->assertSame($longEncrypted1, $longEncrypted2);
        $this->assertSame($longString, $e->decryptString($longEncrypted1));
    }

    public function test_non_deterministic_encryption(): void
    {
        $e = new Encrypter(str_repeat('a', 16));
        $value = 'test data';

        // Test that non-deterministic encryption produces different results for the same input
        $encrypted1 = $e->encrypt($value);
        $encrypted2 = $e->encrypt($value);
        $this->assertNotSame($encrypted1, $encrypted2);

        // Test that both can be decrypted to the same original value
        $this->assertSame($value, $e->decrypt($encrypted1));
        $this->assertSame($value, $e->decrypt($encrypted2));

        // Test with explicit false parameter
        $encrypted3 = $e->encrypt($value, true, false);
        $encrypted4 = $e->encrypt($value, true, false);
        $this->assertNotSame($encrypted3, $encrypted4);
        $this->assertSame($value, $e->decrypt($encrypted3));
        $this->assertSame($value, $e->decrypt($encrypted4));

        // Test with array data
        $arrayData = ['key' => 'value', 'number' => 42];
        $encryptedArray1 = $e->encrypt($arrayData);
        $encryptedArray2 = $e->encrypt($arrayData);
        $this->assertNotSame($encryptedArray1, $encryptedArray2);
        $this->assertSame($arrayData, $e->decrypt($encryptedArray1));
        $this->assertSame($arrayData, $e->decrypt($encryptedArray2));
    }

    public function test_non_deterministic_string_encryption(): void
    {
        $e = new Encrypter(str_repeat('a', 16));
        $value = 'test string';

        // Test that non-deterministic string encryption produces different results
        $encrypted1 = $e->encryptString($value);
        $encrypted2 = $e->encryptString($value);
        $this->assertNotSame($encrypted1, $encrypted2);

        // Test that both can be decrypted to the same original value
        $this->assertSame($value, $e->decryptString($encrypted1));
        $this->assertSame($value, $e->decryptString($encrypted2));

        // Test with explicit false parameter
        $encrypted3 = $e->encryptString($value, false);
        $encrypted4 = $e->encryptString($value, false);
        $this->assertNotSame($encrypted3, $encrypted4);
        $this->assertSame($value, $e->decryptString($encrypted3));
        $this->assertSame($value, $e->decryptString($encrypted4));

        // Test empty string
        $emptyEncrypted1 = $e->encryptString('');
        $emptyEncrypted2 = $e->encryptString('');
        $this->assertNotSame($emptyEncrypted1, $emptyEncrypted2);
        $this->assertSame('', $e->decryptString($emptyEncrypted1));
        $this->assertSame('', $e->decryptString($emptyEncrypted2));
    }

    public function test_deterministic_encryption_with_different_keys(): void
    {
        $key1 = str_repeat('a', 16);
        $key2 = str_repeat('b', 16);
        $e1 = new Encrypter($key1);
        $e2 = new Encrypter($key2);
        $value = 'test data';

        // Test that same value with different keys produces different deterministic results
        $encrypted1 = $e1->encrypt($value, true, true);
        $encrypted2 = $e2->encrypt($value, true, true);
        $this->assertNotSame($encrypted1, $encrypted2);

        // Test that each key can decrypt its own deterministic encryption
        $this->assertSame($value, $e1->decrypt($encrypted1));
        $this->assertSame($value, $e2->decrypt($encrypted2));

        // Test that keys cannot decrypt each other's deterministic encryption
        $this->expectException(DecryptException::class);
        $e1->decrypt($encrypted2);
    }

    public function test_deterministic_vs_non_deterministic_encryption(): void
    {
        $e = new Encrypter(str_repeat('a', 16));
        $value = 'test data';

        // Test that the same value produces different results between deterministic and non-deterministic
        $deterministicEncrypted = $e->encrypt($value, true, true);
        $nonDeterministicEncrypted = $e->encrypt($value, true, false);
        $this->assertNotSame($deterministicEncrypted, $nonDeterministicEncrypted);

        // Test that both can be decrypted correctly
        $this->assertSame($value, $e->decrypt($deterministicEncrypted));
        $this->assertSame($value, $e->decrypt($nonDeterministicEncrypted));

        // Test multiple non-deterministic encryptions are different from deterministic
        $nonDeterministic1 = $e->encrypt($value);
        $nonDeterministic2 = $e->encrypt($value);
        $this->assertNotSame($deterministicEncrypted, $nonDeterministic1);
        $this->assertNotSame($deterministicEncrypted, $nonDeterministic2);
        $this->assertNotSame($nonDeterministic1, $nonDeterministic2);
    }

    public function test_deterministic_encryption_with_aead_cipher(): void
    {
        $e = new Encrypter(str_repeat('b', 32), 'AES-256-GCM');
        $value = 'test data with AEAD';

        // Test deterministic encryption with AEAD cipher
        $encrypted1 = $e->encrypt($value, true, true);
        $encrypted2 = $e->encrypt($value, true, true);
        $this->assertSame($encrypted1, $encrypted2);

        // Test decryption works correctly
        $this->assertSame($value, $e->decrypt($encrypted1));
        $this->assertSame($value, $e->decrypt($encrypted2));

        // Test string encryption with AEAD
        $stringEncrypted1 = $e->encryptString($value, true);
        $stringEncrypted2 = $e->encryptString($value, true);
        $this->assertSame($stringEncrypted1, $stringEncrypted2);
        $this->assertSame($value, $e->decryptString($stringEncrypted1));
    }

    public function test_deterministic_encryption_consistency_across_instances(): void
    {
        $key = str_repeat('a', 16);
        $value = 'consistency test';

        // Create multiple encrypter instances with the same key
        $e1 = new Encrypter($key);
        $e2 = new Encrypter($key);
        $e3 = new Encrypter($key);

        // Test that deterministic encryption is consistent across instances
        $encrypted1 = $e1->encrypt($value, true, true);
        $encrypted2 = $e2->encrypt($value, true, true);
        $encrypted3 = $e3->encrypt($value, true, true);

        $this->assertSame($encrypted1, $encrypted2);
        $this->assertSame($encrypted2, $encrypted3);

        // Test cross-instance decryption
        $this->assertSame($value, $e1->decrypt($encrypted2));
        $this->assertSame($value, $e2->decrypt($encrypted3));
        $this->assertSame($value, $e3->decrypt($encrypted1));
    }
}
