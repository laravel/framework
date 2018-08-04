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
        $this->assertSame('bcrypt', password_get_info($value)['algoName']);
    }

    public function testBasicArgon2iHashing()
    {
        if (! defined('PASSWORD_ARGON2I')) {
            $this->markTestSkipped('PHP not compiled with Argon2i hashing support.');
        }

        $hasher = new \Illuminate\Hashing\ArgonHasher;
        $value = $hasher->make('password');
        $this->assertNotSame('password', $value);
        $this->assertTrue($hasher->check('password', $value));
        $this->assertFalse($hasher->needsRehash($value));
        $this->assertTrue($hasher->needsRehash($value, ['threads' => 1]));
        $this->assertSame('argon2i', password_get_info($value)['algoName']);
    }

    public function testBasicArgon2idHashing()
    {
        if (! defined('PASSWORD_ARGON2ID')) {
            $this->markTestSkipped('PHP not compiled with Argon2id hashing support.');
        }

        $hasher = new \Illuminate\Hashing\ArgonHasher(['type' => 'argon2id']);
        $value = $hasher->make('password');
        $this->assertNotSame('password', $value);
        $this->assertTrue($hasher->check('password', $value));
        $this->assertFalse($hasher->needsRehash($value));
        $this->assertTrue($hasher->needsRehash($value, ['threads' => 1]));
        $this->assertSame('argon2id', password_get_info($value)['algoName']);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testBasicBcryptVerification()
    {
        if (! defined('PASSWORD_ARGON2I')) {
            $this->markTestSkipped('PHP not compiled with argon2i hashing support.');
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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Argon "argon55-x" hashing type is not supported.
     */
    public function testExceptionIsThrownForAnUnsupportedArgonTypeOnConstruction()
    {
        new \Illuminate\Hashing\ArgonHasher(['type' => 'argon55-x']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Argon "argon55-x" hashing type is not supported.
     */
    public function testExceptionIsThrownForAnUnsupportedArgonTypeOnPasswordMakeMethod()
    {
        $hasher = new \Illuminate\Hashing\ArgonHasher;
        $hasher->make('password', ['type' => 'argon55-x']);
    }
}
