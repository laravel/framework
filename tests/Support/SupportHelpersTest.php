<?php

class SupportHelpersTest extends PHPUnit_Framework_TestCase {

	public function testArrayBuild()
	{
		$this->assertEquals(['foo' => 'bar'], array_build(['foo' => 'bar'], function($key, $value)
		{
			return [$key, $value];
		}));
	}


	public function testArrayDot()
	{
		$array = array_dot(['name' => 'taylor', 'languages' => ['php' => true]]);
		$this->assertEquals($array, ['name' => 'taylor', 'languages.php' => true]);
	}


	public function testArrayGet()
	{
		$array = ['names' => ['developer' => 'taylor']];
		$this->assertEquals('taylor', array_get($array, 'names.developer'));
		$this->assertEquals('dayle', array_get($array, 'names.otherDeveloper', 'dayle'));
		$this->assertEquals('dayle', array_get($array, 'names.otherDeveloper', function() { return 'dayle'; }));
	}


	public function testArraySet()
	{
		$array = [];
		array_set($array, 'names.developer', 'taylor');
		$this->assertEquals('taylor', $array['names']['developer']);
	}


	public function testArrayForget()
	{
		$array = ['names' => ['developer' => 'taylor', 'otherDeveloper' => 'dayle']];
		array_forget($array, 'names.developer');
		$this->assertFalse(isset($array['names']['developer']));
		$this->assertTrue(isset($array['names']['otherDeveloper']));
	}


	public function testArrayPluckWithArrayAndObjectValues()
	{
		$array = [(object) ['name' => 'taylor', 'email' => 'foo'], ['name' => 'dayle', 'email' => 'bar']];
		$this->assertEquals(['taylor', 'dayle'], array_pluck($array, 'name'));
		$this->assertEquals(['taylor' => 'foo', 'dayle' => 'bar'], array_pluck($array, 'email', 'name'));
	}


	public function testArrayExcept()
	{
		$array = ['name' => 'taylor', 'age' => 26];
		$this->assertEquals(['age' => 26], array_except($array, ['name']));
	}


	public function testArrayOnly()
	{
		$array = ['name' => 'taylor', 'age' => 26];
		$this->assertEquals(['name' => 'taylor'], array_only($array, ['name']));
	}


	public function testArrayDivide()
	{
		$array = ['name' => 'taylor'];
		list($keys, $values) = array_divide($array);
		$this->assertEquals(['name'], $keys);
		$this->assertEquals(['taylor'], $values);
	}


	public function testArrayFirst()
	{
		$array = ['name' => 'taylor', 'otherDeveloper' => 'dayle'];
		$this->assertEquals('dayle', array_first($array, function($key, $value) { return $value == 'dayle'; }));
	}


	public function testArrayFetch()
	{
		$data = [
			'post-1' => [
				'comments' => [
					'tags' => [
						'#foo', '#bar',
					],
				],
			],
			'post-2' => [
				'comments' => [
					'tags' => [
						'#baz',
					],
				],
			],
		];

		$this->assertEquals([
			0 => [
				'tags' => [
					'#foo', '#bar',
				],
			],
			1 => [
				'tags' => [
					'#baz',
				],
			],
		], array_fetch($data, 'comments'));

		$this->assertEquals([['#foo', '#bar'], ['#baz']], array_fetch($data, 'comments.tags'));
	}


	public function testArrayFlatten()
	{
		$this->assertEquals(['#foo', '#bar', '#baz'], array_flatten([['#foo', '#bar'], ['#baz']]));
	}


	public function testStrIs()
	{
		$this->assertTrue(str_is('*.dev', 'localhost.dev'));
		$this->assertTrue(str_is('a', 'a'));
		$this->assertTrue(str_is('/', '/'));
		$this->assertTrue(str_is('*dev*', 'localhost.dev'));
		$this->assertTrue(str_is('foo?bar', 'foo?bar'));
		$this->assertFalse(str_is('*something', 'foobar'));
		$this->assertFalse(str_is('foo', 'bar'));
		$this->assertFalse(str_is('foo.*', 'foobar'));
		$this->assertFalse(str_is('foo.ar', 'foobar'));
		$this->assertFalse(str_is('foo?bar', 'foobar'));
		$this->assertFalse(str_is('foo?bar', 'fobar'));
	}


	public function testStartsWith()
	{
		$this->assertTrue(starts_with('jason', 'jas'));
		$this->assertTrue(starts_with('jason', ['jas']));
		$this->assertFalse(starts_with('jason', 'day'));
		$this->assertFalse(starts_with('jason', ['day']));
	}


	public function testEndsWith()
	{
		$this->assertTrue(ends_with('jason', 'on'));
		$this->assertTrue(ends_with('jason', ['on']));
		$this->assertFalse(ends_with('jason', 'no'));
		$this->assertFalse(ends_with('jason', ['no']));
	}


	public function testStrContains()
	{
		$this->assertTrue(str_contains('taylor', 'ylo'));
		$this->assertTrue(str_contains('taylor', ['ylo']));
		$this->assertFalse(str_contains('taylor', 'xxx'));
		$this->assertFalse(str_contains('taylor', ['xxx']));
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


	public function testObjectGet()
	{
		$class = new StdClass;
		$class->name = new StdClass;
		$class->name->first = 'Taylor';

		$this->assertEquals('Taylor', object_get($class, 'name.first'));
	}


	public function testArraySort()
	{
		$array = [
			['name' => 'baz'],
			['name' => 'foo'],
			['name' => 'bar'],
		];

		$this->assertEquals([
			['name' => 'bar'],
			['name' => 'baz'],
			['name' => 'foo']],
		array_values(array_sort($array, function($v) { return $v['name']; })));
	}

}
