<?php

namespace Illuminate\Tests\Hashing;

use PHPUnit\Framework\TestCase;

class HasherTest extends TestCase
{
    public function testBasicBcryptHashing()
    {
        $hasher = new \Illuminate\Hashing\BcryptHasher;
        $value = $hasher->make('password');
        $this->assertNotSame('password', $value);
        $this->assertTrue($hasher->check('password', $value));
        $this->assertFalse($hasher->needsRehash($value));
        $this->assertTrue($hasher->needsRehash($value, ['rounds' => 1]));
    }

    public function testBasicArgonHashing()
    {
        if (! defined('PASSWORD_ARGON2I')) {
            $this->markTestSkipped('PHP not compiled with argon2 hashing support.');
        }

        $hasher = new \Illuminate\Hashing\ArgonHasher;
        $value = $hasher->make('password');
        $this->assertNotSame('password', $value);
        $this->assertTrue($hasher->check('password', $value));
        $this->assertFalse($hasher->needsRehash($value));
        $this->assertTrue($hasher->needsRehash($value, ['threads' => 1]));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testBasicBcryptVerification()
    {
        if (! defined('PASSWORD_ARGON2I')) {
            $this->markTestSkipped('PHP not compiled with argon2 hashing support.');
        }

        $argonHasher = new \Illuminate\Hashing\ArgonHasher;
        $argonHashed = $argonHasher->make('password');
        (new \Illuminate\Hashing\BcryptHasher)->check('password', $argonHashed);
    }

    /**
     * @expectedException \Exception
     */
    public function testBasicArgonVerification()
    {
        $bcryptHasher = new \Illuminate\Hashing\BcryptHasher;
        $bcryptHashed = $bcryptHasher->make('password');
        (new \Illuminate\Hashing\ArgonHasher)->check('password', $bcryptHashed);
    }
}
