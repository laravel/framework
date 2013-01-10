<?php

class BcryptHasherTest extends PHPUnit_Framework_TestCase {

	public function testBasicHashing()
	{
		$hasher = new Illuminate\Hashing\BcryptHasher;
		$value = $hasher->make('password');
		$this->assertTrue($value !== 'password');
		$this->assertTrue($hasher->check('password', $value));
	}

}