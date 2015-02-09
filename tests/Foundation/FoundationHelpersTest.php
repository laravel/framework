<?php

class FoundationHelpersTest extends PHPUnit_Framework_TestCase {

	public function testClosure()
	{
		$closure1 = (new FoundationTestClosure)->foo();
		$closure2 = closure('FoundationTestClosure@bar');
		$closure3 = closure(['FoundationTestClosure', 'baz']);
		$closure4 = closure('strtoupper');

		$this->assertInstanceOf('\Closure', $closure1);
		$this->assertInstanceOf('\Closure', $closure2);
		$this->assertInstanceOf('\Closure', $closure3);
		$this->assertInstanceOf('\Closure', $closure4);
		$this->assertEquals(['baz', []], $closure1());
		$this->assertEquals(['bar', []], $closure2());
		$this->assertEquals(['baz', []], $closure3());
		$this->assertEquals(['baz', [1, 2]], $closure1(1, 2));
		$this->assertEquals(['bar', [1, 2]], $closure2(1, 2));
		$this->assertEquals(['baz', [1, 2]], $closure3(1, 2));
		$this->assertEquals('FOO', $closure4('foo'));
	}

}

class FoundationTestClosure {

	public function foo(){ return closure([$this, 'baz']); }

	public function bar(){ return ['bar', func_get_args()]; }

	public static function baz(){ return ['baz', func_get_args()]; }

}
