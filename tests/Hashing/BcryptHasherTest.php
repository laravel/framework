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

    /**
     * @expectedException LengthException
     * @expectedExceptionMessageRegExp /Passwords longer than \d+ characters are truncated when using Bcrypt\./
     */
    public function testLongPasswords()
    {
        $hasher = new Illuminate\Hashing\BcryptHasher;
        $hasher->make('ThisIsAReallyLongPasswordStringThatWillTriggerALengthExceptionWhenItIsUsed');
    }

    public function testLongPasswordsAcceptedWhenIgnoringLengthCheck()
    {
        $hasher = new Illuminate\Hashing\BcryptHasher;
        $value = $hasher->make('ThisIsAReallyLongPasswordStringThatWouldOtherwiseTriggerALengthExceptionWhenItIsUsed', ['ignore_password_length' => true]);
        $this->assertTrue($hasher->check('ThisIsAReallyLongPasswordStringThatWouldOtherwiseTriggerALengthExceptionWhenItIsUsed', $value));
    }
}
