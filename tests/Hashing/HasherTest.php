<?php

namespace Illuminate\Tests\Hashing;

use PHPUnit\Framework\TestCase;

class HasherTest extends TestCase
{
    public function testBasicHashing()
    {
        $hasher = new \Illuminate\Hashing\Hasher;
        $value = $hasher->make('password');
        $this->assertNotSame('password', $value);
        $this->assertTrue($hasher->check('password', $value));
        $this->assertFalse($hasher->needsRehash($value));
        $this->assertTrue($hasher->needsRehash($value, ['cost' => 1]));
    }
}
