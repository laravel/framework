<?php

class BcryptHasherTest extends PHPUnit_Framework_TestCase
{
    public function testBasicHashing()
    {
        $hasher = new Illuminate\Hashing\BcryptHasher;
        $value = $hasher->make('password');
        $this->assertNotSame('password', $value);
        $this->assertTrue($hasher->check('password', $value));
        $this->assertFalse($hasher->needsRehash($value));
        $this->assertTrue($hasher->needsRehash($value, ['rounds' => 1]));
    }

    public function testIsHash()
    {
        $hasher = new Illuminate\Hashing\BcryptHasher;
        $realHash = $hasher->make('password');
        $alternateHash = $hasher->make('password', ['cost' => 1]);
        $fakeHash = sha1('password');
        $this->assertTrue($hasher->isHash($realHash));
        $this->assertFalse($hasher->isHash($fakeHash));
        $this->assertTrue($hasher->isHash($alternateHash));
        $this->assertFalse($hasher->isHash($alternateHash, ['cost' => 2], true));
    }
}
