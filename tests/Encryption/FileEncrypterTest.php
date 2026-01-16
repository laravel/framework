<?php

namespace Illuminate\Tests\Encryption;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Encryption\FileEncrypter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FileEncrypterTest extends TestCase
{
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/laravel-file-encrypter-test-'.uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->cleanDirectory($this->tempDir);
        rmdir($this->tempDir);

        parent::tearDown();
    }

    protected function cleanDirectory(string $directory): void
    {
        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.'/'.$item;

            if (is_dir($path)) {
                $this->cleanDirectory($path);
                rmdir($path);
            } else {
                unlink($path);
            }
        }
    }

    public function test_requires32_byte_key(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File encryption requires a 32-byte key for AES-256-GCM.');

        new FileEncrypter(str_repeat('a', 16));
    }

    public function test_requires_minimum_chunk_size(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Chunk size must be at least 1024 bytes.');

        new FileEncrypter(str_repeat('a', 32), 512);
    }

    public function test_encrypts_and_decrypts_small_file(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key);

        $sourcePath = $this->tempDir.'/small.txt';
        $encryptedPath = $this->tempDir.'/small.txt.enc';
        $decryptedPath = $this->tempDir.'/small-decrypted.txt';

        $content = 'Hello, World!';
        file_put_contents($sourcePath, $content);

        $encrypter->encryptFile($sourcePath, $encryptedPath);

        $this->assertFileExists($encryptedPath);
        $this->assertNotEquals($content, file_get_contents($encryptedPath));

        $encrypter->decryptFile($encryptedPath, $decryptedPath);

        $this->assertFileExists($decryptedPath);
        $this->assertEquals($content, file_get_contents($decryptedPath));
    }

    public function test_encrypts_and_decrypts_empty_file(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key);

        $sourcePath = $this->tempDir.'/empty.txt';
        $encryptedPath = $this->tempDir.'/empty.txt.enc';
        $decryptedPath = $this->tempDir.'/empty-decrypted.txt';

        file_put_contents($sourcePath, '');

        $encrypter->encryptFile($sourcePath, $encryptedPath);

        $this->assertFileExists($encryptedPath);

        $encrypter->decryptFile($encryptedPath, $decryptedPath);

        $this->assertFileExists($decryptedPath);
        $this->assertEquals('', file_get_contents($decryptedPath));
    }

    public function test_encrypts_and_decrypts_large_multi_chunk_file(): void
    {
        $key = random_bytes(32);
        $chunkSize = 1024; // 1KB chunks
        $encrypter = new FileEncrypter($key, $chunkSize);

        $sourcePath = $this->tempDir.'/large.txt';
        $encryptedPath = $this->tempDir.'/large.txt.enc';
        $decryptedPath = $this->tempDir.'/large-decrypted.txt';

        // Create a file larger than 3 chunks
        $content = random_bytes($chunkSize * 3 + 512);
        file_put_contents($sourcePath, $content);

        $encrypter->encryptFile($sourcePath, $encryptedPath);

        $this->assertFileExists($encryptedPath);

        $encrypter->decryptFile($encryptedPath, $decryptedPath);

        $this->assertFileExists($decryptedPath);
        $this->assertEquals($content, file_get_contents($decryptedPath));
    }

    public function test_encrypts_with_exact_chunk_alignment(): void
    {
        $key = random_bytes(32);
        $chunkSize = 1024;
        $encrypter = new FileEncrypter($key, $chunkSize);

        $sourcePath = $this->tempDir.'/aligned.txt';
        $encryptedPath = $this->tempDir.'/aligned.txt.enc';
        $decryptedPath = $this->tempDir.'/aligned-decrypted.txt';

        // Create a file exactly 2 chunks in size
        $content = random_bytes($chunkSize * 2);
        file_put_contents($sourcePath, $content);

        $encrypter->encryptFile($sourcePath, $encryptedPath);
        $encrypter->decryptFile($encryptedPath, $decryptedPath);

        $this->assertEquals($content, file_get_contents($decryptedPath));
    }

    public function test_detects_encrypted_files(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key);

        $plainPath = $this->tempDir.'/plain.txt';
        $encryptedPath = $this->tempDir.'/encrypted.enc';

        file_put_contents($plainPath, 'Hello, World!');

        $this->assertFalse($encrypter->isEncrypted($plainPath));

        $encrypter->encryptFile($plainPath, $encryptedPath);

        $this->assertTrue($encrypter->isEncrypted($encryptedPath));
    }

    public function test_is_encrypted_returns_false_for_non_existent_file(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key);

        $this->assertFalse($encrypter->isEncrypted($this->tempDir.'/non-existent.txt'));
    }

    public function test_decrypted_contents_returns_original_content(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key);

        $sourcePath = $this->tempDir.'/source.txt';
        $encryptedPath = $this->tempDir.'/source.txt.enc';

        $content = 'Hello, World! This is a test.';
        file_put_contents($sourcePath, $content);

        $encrypter->encryptFile($sourcePath, $encryptedPath);

        $decryptedContent = $encrypter->decryptedContents($encryptedPath);

        $this->assertEquals($content, $decryptedContent);
    }

    public function test_decrypted_stream_yields_chunks(): void
    {
        $key = random_bytes(32);
        $chunkSize = 1024;
        $encrypter = new FileEncrypter($key, $chunkSize);

        $sourcePath = $this->tempDir.'/stream.txt';
        $encryptedPath = $this->tempDir.'/stream.txt.enc';

        // Create a multi-chunk file
        $content = random_bytes($chunkSize * 3);
        file_put_contents($sourcePath, $content);

        $encrypter->encryptFile($sourcePath, $encryptedPath);

        $chunks = [];
        $encrypter->decryptedStream($encryptedPath, function ($chunk) use (&$chunks) {
            $chunks[] = $chunk;
        });

        $this->assertGreaterThan(1, count($chunks));
        $this->assertEquals($content, implode('', $chunks));
    }

    public function test_throws_on_invalid_magic_bytes(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key);

        $invalidPath = $this->tempDir.'/invalid.enc';
        file_put_contents($invalidPath, 'XXXX'.str_repeat("\x00", 28)); // Wrong magic bytes

        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('Invalid encrypted file format.');

        $encrypter->decryptFile($invalidPath, $this->tempDir.'/output.txt');
    }

    public function test_throws_on_tampered_header(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key);

        $sourcePath = $this->tempDir.'/source.txt';
        $encryptedPath = $this->tempDir.'/tampered.enc';

        file_put_contents($sourcePath, 'Test content');
        $encrypter->encryptFile($sourcePath, $encryptedPath);

        // Tamper with the header (modify file size bytes)
        $contents = file_get_contents($encryptedPath);
        $contents[12] = chr(ord($contents[12]) ^ 0xFF); // XOR a byte in the file size field
        file_put_contents($encryptedPath, $contents);

        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('Invalid header HMAC');

        $encrypter->decryptFile($encryptedPath, $this->tempDir.'/output.txt');
    }

    public function test_throws_on_tampered_chunk(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key, 1024);

        $sourcePath = $this->tempDir.'/source.txt';
        $encryptedPath = $this->tempDir.'/tampered-chunk.enc';

        file_put_contents($sourcePath, str_repeat('A', 2048)); // 2 chunks
        $encrypter->encryptFile($sourcePath, $encryptedPath);

        // Tamper with encrypted data after header (byte 50)
        $contents = file_get_contents($encryptedPath);
        $contents[50] = chr(ord($contents[50]) ^ 0xFF);
        file_put_contents($encryptedPath, $contents);

        $this->expectException(DecryptException::class);

        $encrypter->decryptFile($encryptedPath, $this->tempDir.'/output.txt');
    }

    public function test_throws_on_wrong_key(): void
    {
        $key1 = random_bytes(32);
        $key2 = random_bytes(32);
        $encrypter1 = new FileEncrypter($key1);
        $encrypter2 = new FileEncrypter($key2);

        $sourcePath = $this->tempDir.'/source.txt';
        $encryptedPath = $this->tempDir.'/encrypted.enc';

        file_put_contents($sourcePath, 'Secret content');
        $encrypter1->encryptFile($sourcePath, $encryptedPath);

        $this->expectException(DecryptException::class);

        $encrypter2->decryptFile($encryptedPath, $this->tempDir.'/output.txt');
    }

    public function test_decrypts_with_previous_keys(): void
    {
        $oldKey = random_bytes(32);
        $newKey = random_bytes(32);

        $oldEncrypter = new FileEncrypter($oldKey);
        $newEncrypter = (new FileEncrypter($newKey))->previousKeys([$oldKey]);

        $sourcePath = $this->tempDir.'/source.txt';
        $encryptedPath = $this->tempDir.'/encrypted.enc';
        $decryptedPath = $this->tempDir.'/decrypted.txt';

        $content = 'Secret content encrypted with old key';
        file_put_contents($sourcePath, $content);

        // Encrypt with old key
        $oldEncrypter->encryptFile($sourcePath, $encryptedPath);

        // Decrypt with new encrypter that has old key as previous
        $newEncrypter->decryptFile($encryptedPath, $decryptedPath);

        $this->assertEquals($content, file_get_contents($decryptedPath));
    }

    public function test_progress_callback_receives_correct_values(): void
    {
        $key = random_bytes(32);
        $chunkSize = 1024;
        $encrypter = new FileEncrypter($key, $chunkSize);

        $sourcePath = $this->tempDir.'/progress.txt';
        $encryptedPath = $this->tempDir.'/progress.enc';

        // Create a 3-chunk file
        file_put_contents($sourcePath, str_repeat('X', $chunkSize * 3));

        $progressCalls = [];
        $encrypter->encryptFile($sourcePath, $encryptedPath, function ($current, $total) use (&$progressCalls) {
            $progressCalls[] = ['current' => $current, 'total' => $total];
        });

        $this->assertCount(3, $progressCalls);
        $this->assertEquals(['current' => 1, 'total' => 3], $progressCalls[0]);
        $this->assertEquals(['current' => 2, 'total' => 3], $progressCalls[1]);
        $this->assertEquals(['current' => 3, 'total' => 3], $progressCalls[2]);
    }

    public function test_throws_when_source_file_does_not_exist(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key);

        $this->expectException(EncryptException::class);
        $this->expectExceptionMessage('Source file does not exist');

        $encrypter->encryptFile($this->tempDir.'/non-existent.txt', $this->tempDir.'/output.enc');
    }

    public function test_throws_when_encrypted_file_does_not_exist(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key);

        $this->expectException(DecryptException::class);
        $this->expectExceptionMessage('Encrypted file does not exist');

        $encrypter->decryptFile($this->tempDir.'/non-existent.enc', $this->tempDir.'/output.txt');
    }

    public function test_get_chunk_size_returns_configured_value(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key, 8192);

        $this->assertEquals(8192, $encrypter->getChunkSize());
    }

    public function test_get_key_returns_key(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key);

        $this->assertEquals($key, $encrypter->getKey());
    }

    public function test_get_all_keys_includes_previous_keys(): void
    {
        $key = random_bytes(32);
        $previousKey = random_bytes(32);
        $encrypter = (new FileEncrypter($key))->previousKeys([$previousKey]);

        $allKeys = $encrypter->getAllKeys();

        $this->assertCount(2, $allKeys);
        $this->assertEquals($key, $allKeys[0]);
        $this->assertEquals($previousKey, $allKeys[1]);
    }

    public function test_previous_keys_validates_key_length(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('All keys must be 32 bytes for AES-256-GCM.');

        $encrypter->previousKeys([str_repeat('a', 16)]);
    }

    public function test_decrypt_file_defaults_to_stripping_enc_extension(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key);

        $sourcePath = $this->tempDir.'/test.txt';
        $encryptedPath = $this->tempDir.'/test.txt.enc';

        $content = 'Test content';
        file_put_contents($sourcePath, $content);

        $encrypter->encryptFile($sourcePath, $encryptedPath);
        unlink($sourcePath); // Remove original

        $encrypter->decryptFile($encryptedPath); // Should decrypt to test.txt

        $this->assertFileExists($sourcePath);
        $this->assertEquals($content, file_get_contents($sourcePath));
    }

    public function test_encrypt_file_defaults_to_adding_enc_extension(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key);

        $sourcePath = $this->tempDir.'/default.txt';

        file_put_contents($sourcePath, 'Test content');

        $encrypter->encryptFile($sourcePath); // Should encrypt to default.txt.enc

        $this->assertFileExists($sourcePath.'.enc');
        $this->assertTrue($encrypter->isEncrypted($sourcePath.'.enc'));
    }

    public function test_binary_data_preserved(): void
    {
        $key = random_bytes(32);
        $encrypter = new FileEncrypter($key);

        $sourcePath = $this->tempDir.'/binary.bin';
        $encryptedPath = $this->tempDir.'/binary.bin.enc';
        $decryptedPath = $this->tempDir.'/binary-decrypted.bin';

        // Create binary content with all byte values
        $content = '';
        for ($i = 0; $i < 256; $i++) {
            $content .= chr($i);
        }
        $content = str_repeat($content, 10); // Make it larger

        file_put_contents($sourcePath, $content);

        $encrypter->encryptFile($sourcePath, $encryptedPath);
        $encrypter->decryptFile($encryptedPath, $decryptedPath);

        $this->assertEquals($content, file_get_contents($decryptedPath));
    }
}
