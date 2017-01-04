<?php

namespace Illuminate\Tests\Hashing;

use PHPUnit\Framework\TestCase;

class BcryptHasherTest extends TestCase
{
    public function testBasicHashing()
    {
        $hasher = new \Illuminate\Hashing\BcryptHasher;
        $value = $hasher->make('password');
        $this->assertNotSame('password', $value);
        $this->assertTrue($hasher->check('password', $value));
        $this->assertFalse($hasher->needsRehash($value));
        $this->assertTrue($hasher->needsRehash($value, ['rounds' => 1]));
    }
}
