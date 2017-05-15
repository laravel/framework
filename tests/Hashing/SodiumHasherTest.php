<?php

class SodiumHasherTest extends PHPUnit_Framework_TestCase
{
    /**
     * @requires extension libsodium
     */
    public function testBasicHashing()
    {
        $hasher = new Illuminate\Hashing\SodiumHasher;
        $value = $hasher->make('password');
        $this->assertNotSame('password', $value);
        $this->assertTrue($hasher->check('password', $value));
        $this->assertFalse($hasher->needsRehash($value));
    }
}
