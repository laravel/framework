<?php

class BcryptHasherTest extends PHPUnit_Framework_TestCase {

	public function testBasicHashing()
	{
		$hasher = new Illuminate\Hashing\BcryptHasher;
		$value = $hasher->make('password');
		$this->assertNotSame('password', $value);
		$this->assertTrue($hasher->check('password', $value));
		$this->assertTrue(!$hasher->needsRehash($value));
		$this->assertTrue($hasher->needsRehash($value, ['rounds' => 1]));
	}

}
