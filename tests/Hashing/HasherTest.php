<?php

namespace Illuminate\Tests\Hashing;

use Illuminate\Hashing\Argon2IdHasher;
use Illuminate\Hashing\ArgonHasher;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HasherTest extends TestCase
{
    private string $randomValueToHash;

    private function __construct()
    {
        $this->randomValueToHash = Str::random(rand(6, 12));
    }

    public function testBasicBcryptHashing()
    {
        $hasher = new BcryptHasher;
        $value = $hasher->make($this->randomValueToHash);
        $this->assertNotSame($this->randomValueToHash, $value);
        $this->assertTrue($hasher->check($this->randomValueToHash, $value));
        $this->assertFalse($hasher->needsRehash($value));
        $this->assertTrue($hasher->needsRehash($value, ['rounds' => 1]));
        $this->assertSame('bcrypt', password_get_info($value)['algoName']);
    }

    public function testBasicArgon2iHashing()
    {
        if (! defined('PASSWORD_ARGON2I')) {
            $this->markTestSkipped('PHP not compiled with Argon2i hashing support.');
        }

        $hasher = new ArgonHasher;
        $value = $hasher->make($this->randomValueToHash);
        $this->assertNotSame($this->randomValueToHash, $value);
        $this->assertTrue($hasher->check($this->randomValueToHash, $value));
        $this->assertFalse($hasher->needsRehash($value));
        $this->assertTrue($hasher->needsRehash($value, ['threads' => 1]));
        $this->assertSame('argon2i', password_get_info($value)['algoName']);
    }

    public function testBasicArgon2idHashing()
    {
        if (! defined('PASSWORD_ARGON2ID')) {
            $this->markTestSkipped('PHP not compiled with Argon2id hashing support.');
        }

        $hasher = new Argon2IdHasher;
        $value = $hasher->make($this->randomValueToHash);
        $this->assertNotSame($this->randomValueToHash, $value);
        $this->assertTrue($hasher->check($this->randomValueToHash, $value));
        $this->assertFalse($hasher->needsRehash($value));
        $this->assertTrue($hasher->needsRehash($value, ['threads' => 1]));
        $this->assertSame('argon2id', password_get_info($value)['algoName']);
    }

    /**
     * @depends testBasicBcryptHashing
     */
    public function testBasicBcryptVerification()
    {
        $this->expectException(RuntimeException::class);

        if (! defined('PASSWORD_ARGON2I')) {
            $this->markTestSkipped('PHP not compiled with Argon2i hashing support.');
        }

        $argonHasher = new ArgonHasher(['verify' => true]);
        $argonHashed = $argonHasher->make($this->randomValueToHash);
        (new BcryptHasher(['verify' => true]))->check($this->randomValueToHash, $argonHashed);
    }

    /**
     * @depends testBasicArgon2iHashing
     */
    public function testBasicArgon2iVerification()
    {
        $this->expectException(RuntimeException::class);

        $bcryptHasher = new BcryptHasher(['verify' => true]);
        $bcryptHashed = $bcryptHasher->make($this->randomValueToHash);
        (new ArgonHasher(['verify' => true]))->check($this->randomValueToHash, $bcryptHashed);
    }

    /**
     * @depends testBasicArgon2idHashing
     */
    public function testBasicArgon2idVerification()
    {
        $this->expectException(RuntimeException::class);

        $bcryptHasher = new BcryptHasher(['verify' => true]);
        $bcryptHashed = $bcryptHasher->make($this->randomValueToHash);
        (new Argon2IdHasher(['verify' => true]))->check($this->randomValueToHash, $bcryptHashed);
    }
}
