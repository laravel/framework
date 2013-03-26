<?php

class SupportHelpersTest extends PHPUnit_Framework_TestCase {

	public function testArrayDot()
	{
		$array = array_dot(array('name' => 'taylor', 'languages' => array('php' => true)));
		$this->assertEquals($array, array('name' => 'taylor', 'languages.php' => true));
	}


	public function testArrayGet()
	{
		$array = array('names' => array('developer' => 'taylor'));
		$this->assertEquals('taylor', array_get($array, 'names.developer'));
		$this->assertEquals('dayle', array_get($array, 'names.otherDeveloper', 'dayle'));
		$this->assertEquals('dayle', array_get($array, 'names.otherDeveloper', function() { return 'dayle'; }));
	}


	public function testArraySet()
	{
		$array = array();
		array_set($array, 'names.developer', 'taylor');
		$this->assertEquals('taylor', $array['names']['developer']);
	}


	public function testArrayForget()
	{
		$array = array('names' => array('developer' => 'taylor', 'otherDeveloper' => 'dayle'));
		array_forget($array, 'names.developer');
		$this->assertFalse(isset($array['names']['developer']));
		$this->assertTrue(isset($array['names']['otherDeveloper']));
	}


	public function testArrayPluck()
	{
		$array = array(array('name' => 'taylor'), array('name' => 'dayle'));
		$this->assertEquals(array('taylor', 'dayle'), array_pluck($array, 'name'));
	}


	public function testArrayExcept()
	{
		$array = array('name' => 'taylor', 'age' => 26);
		$this->assertEquals(array('age' => 26), array_except($array, array('name')));
	}


	public function testArrayOnly()
	{
		$array = array('name' => 'taylor', 'age' => 26);
		$this->assertEquals(array('name' => 'taylor'), array_only($array, array('name')));
	}


	public function testArrayDivide()
	{
		$array = array('name' => 'taylor');
		list($keys, $values) = array_divide($array);
		$this->assertEquals(array('name'), $keys);
		$this->assertEquals(array('taylor'), $values);
	}


	public function testArrayFirst()
	{
		$array = array('name' => 'taylor', 'otherDeveloper' => 'dayle');
		$this->assertEquals('dayle', array_first($array, function($key, $value) { return $value == 'dayle'; }));
	}


	public function testArrayFetch()
	{
		$data = array(
			'post-1' => array(
				'comments' => array(
					'tags' => array(
						'#foo', '#bar',
					),
				),
			),
			'post-2' => array(
				'comments' => array(
					'tags' => array(
						'#baz',
					),
				),
			),
		);

		$this->assertEquals(array(
			0 => array(
				'tags' => array(
					'#foo', '#bar',
				),
			),
			1 => array(
				'tags' => array(
					'#baz',
				),
			),
		), array_fetch($data, 'comments'));

		$this->assertEquals(array(array('#foo', '#bar'), array('#baz')), array_fetch($data, 'comments.tags'));
	}


	public function testArrayFlatten()
	{
		$this->assertEquals(array('#foo', '#bar', '#baz'), array_flatten(array(array('#foo', '#bar'), array('#baz'))));
	}


	public function testStrIs()
	{
		$this->assertTrue(str_is('*.dev', 'localhost.dev'));
		$this->assertTrue(str_is('a', 'a'));
		$this->assertTrue(str_is('/', '/'));
		$this->assertTrue(str_is('*dev*', 'localhost.dev'));
		$this->assertFalse(str_is('*something', 'foobar'));
		$this->assertFalse(str_is('foo', 'bar'));
	}


	public function testStartsWith()
	{
		$this->assertTrue(starts_with('jason', 'jas'));
		$this->assertFalse(starts_with('jason', 'day'));
	}


	public function testEndsWith()
	{
		$this->assertTrue(ends_with('jason', 'on'));
		$this->assertFalse(ends_with('jason', 'no'));
	}


	public function testStrContains()
	{
		$this->assertTrue(str_contains('taylor', 'ylo'));
		$this->assertFalse(str_contains('taylor', 'xxx'));
	}


	public function testSnakeCase()
	{
		$this->assertEquals('foo_bar', snake_case('fooBar'));
	}


	public function testCamelCase()
	{
		$this->assertEquals('fooBar', camel_case('FooBar'));
		$this->assertEquals('fooBar', camel_case('foo_bar'));
		$this->assertEquals('fooBarBaz', camel_case('Foo-barBaz'));
		$this->assertEquals('fooBarBaz', camel_case('foo-bar_baz'));
	}


	public function testStudlyCase()
	{
		$this->assertEquals('FooBar', studly_case('fooBar'));
		$this->assertEquals('FooBar', studly_case('foo_bar'));
		$this->assertEquals('FooBarBaz', studly_case('foo-barBaz'));
		$this->assertEquals('FooBarBaz', studly_case('foo-bar_baz'));
	}


	public function testValue()
	{
		$this->assertEquals('foo', value('foo'));
		$this->assertEquals('foo', value(function() { return 'foo'; }));
	}

}
