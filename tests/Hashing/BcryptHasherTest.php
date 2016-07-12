<?php

class BcryptHasherTest extends PHPUnit_Framework_TestCase {

	public function testBasicHashing()
	{
		$hasher = new Illuminate\Hashing\BcryptHasher;
		$value = $hasher->make('password');
		$this->assertNotSame('password', $value);
		$this->assertTrue($hasher->check('password', $value));
		$this->assertFalse($hasher->needsRehash($value));
		$this->assertTrue($hasher->needsRehash($value, array('rounds' => 1)));
	}

	public function testHashingSalt()
	{
		$hasher = new Illuminate\Hashing\BcryptHasher;
		$value = $hasher->make('password', array('salt'=>'+twenty-two-characters'));
		$this->assertSame('$2y$10$K3R3ZW50eS10d28tY2hhce3VGEGQa2/qggpQf4ddsmgT2xbM9j5Sa', $value);
	}

}
