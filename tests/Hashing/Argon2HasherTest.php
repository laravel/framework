<?php

namespace Illuminate\Tests\Hashing;

use Illuminate\Hashing\Argon2Hasher;
use PHPUnit\Framework\TestCase;

class Argon2HasherTest extends TestCase
{
    const PLAINTEXT_PASSWORD = 'password';

    public function setUp()
    {
        if (!(new Argon2Hasher)->isSupported()) {
            $this->markTestSkipped('Argon2i hashing not supported.');
        }
    }

    public function testHashPassword()
    {
        $hasher = new Argon2Hasher;
        $hashedPassword = $hasher->make(self::PLAINTEXT_PASSWORD);

        $this->assertNotSame(self::PLAINTEXT_PASSWORD, $hashedPassword);
        $this->assertStringStartsWith(SODIUM_CRYPTO_PWHASH_STRPREFIX, $hashedPassword);
    }

    public function testVerifyPassword()
    {
        $hasher = new Argon2Hasher;
        $hashedPassword = $hasher->make(self::PLAINTEXT_PASSWORD);

        $this->assertTrue($hasher->check(self::PLAINTEXT_PASSWORD, $hashedPassword));
        $this->assertFalse($hasher->check(strrev(self::PLAINTEXT_PASSWORD), $hashedPassword));
    }

    public function testNeedsRehash()
    {
        $hasher = new Argon2Hasher;
        $hashedPassword = $hasher->make(self::PLAINTEXT_PASSWORD);

        $this->assertFalse($hasher->needsRehash($hashedPassword));
        $this->assertTrue($hasher->needsRehash($hashedPassword, ['time_cost' => 1]));
        $this->assertTrue($hasher->needsRehash($hashedPassword, ['memory_cost' => 1]));
    }
}
