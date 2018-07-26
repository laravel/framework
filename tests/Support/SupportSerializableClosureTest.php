<?php

use Illuminate\Support\SerializableClosure as S;

class SupportSerializableClosureTest extends TestCase {

	public function testClosureCanBeSerializedAndRebuilt()
	{
		$f = new S(function() { return 'hello'; });
		$serialized = serialize($f);
		$unserialized = unserialize($serialized);

		/** @var \Closure $unserialized */
		$this->assertEquals('hello', $unserialized());
	}


	public function testClosureCanBeSerializedAndRebuiltAndInheritState()
	{
		$a = 1;
		$b = 1;
		$f = new S(function($i) use ($a, $b)
		{
			return $a + $b + $i;
		});
		$serialized = serialize($f);
		$unserialized = unserialize($serialized);

		/** @var \Closure $unserialized */
		$this->assertEquals(3, $unserialized(1));
	}


	public function testCanGetCodeAndVariablesFromObject()
	{
		$a = 1;
		$b = 2;
		$f = new S(function($i) use ($a, $b)
		{
			return $a + $b + $i;
		});

		$expectedVars = array('a' => 1, 'b' => 2);
		$expectedCode = 'function ($i) use($a, $b) {
    return $a + $b + $i;
};';
		$this->assertEquals($expectedVars, $f->getVariables());
		$this->assertEquals($expectedCode, $f->getCode());
	}

}
