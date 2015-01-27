<?php

use SuperClosure\Serializer;

class SupportSerializableClosureTest extends PHPUnit_Framework_TestCase {

	public function testClosureCanBeSerializedAndRebuilt()
	{
		$serialized = (new Serializer)->serialize(function() { return 'hello'; });

		$unserialized = unserialize($serialized);

		$this->assertEquals('hello', $unserialized());
	}


	public function testClosureCanBeSerializedAndRebuiltAndInheritState()
	{
		$a = 1;
		$b = 1;

		$serialized = (new Serializer)->serialize(function($i) use ($a, $b)
		{
			return $a + $b + $i;
		});

		$unserialized = unserialize($serialized);

		$this->assertEquals(3, $unserialized(1));
	}

}
