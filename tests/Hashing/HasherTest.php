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
}
