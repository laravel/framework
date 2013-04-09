<?php 

use Illuminate\Support\Fluent;

class SupportFluentTest extends PHPUnit_Framework_TestCase {

	/**
	 * Test the Fluent constructor.
	 *
	 * @test
	 */
	public function testAttributesAreSetByConstructor()
	{
		$array  = array('name' => 'Taylor', 'age' => 25);
		$fluent = new Fluent($array);

		$refl = new \ReflectionObject($fluent);
		$attributes = $refl->getProperty('attributes');
		$attributes->setAccessible(true);

		$this->assertEquals($array, $attributes->getValue($fluent));
		$this->assertEquals($array, $fluent->getAttributes());
	}

	/**
	 * Test the Fluent::get() method.
	 *
	 * @test
	 */
	public function testGetMethodReturnsAttribute()
	{
		$fluent = new Fluent(array('name' => 'Taylor'));

		$this->assertEquals('Taylor', $fluent->get('name'));
		$this->assertEquals('Default', $fluent->get('foo', 'Default'));
		$this->assertEquals('Taylor', $fluent->name);
		$this->assertNull($fluent->foo);
	}

	/**
	 * Test the Fluent magic methods can be used to set attributes.
	 *
	 * @test
	 */
	public function testMagicMethodsCanBeUsedToSetAttributes()
	{
		$fluent = new Fluent;

		$fluent->name = 'Taylor';
		$fluent->developer();
		$fluent->age(25);

		$this->assertEquals('Taylor', $fluent->name);
		$this->assertTrue($fluent->developer);
		$this->assertEquals(25, $fluent->age);
		$this->assertInstanceOf('Illuminate\Support\Fluent', $fluent->programmer());
	}

	/**
	 * Test the Fluent::__isset() method.
	 *
	 * @test
	 */
	public function testIssetMagicMethod()
	{
		$array  = array('name' => 'Taylor', 'age' => 25);
		$fluent = new Fluent($array);

		$this->assertTrue(isset($fluent->name));

		unset($fluent->name);

		$this->assertFalse(isset($fluent->name));
	}
}