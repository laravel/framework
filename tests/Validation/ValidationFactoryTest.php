<?php

use Mockery as m;
use Illuminate\Validation\Factory;

class ValidationFactoryTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testMakeMethodCreatesValidValidator()
	{
		$translator = m::mock('Symfony\Component\Translation\TranslatorInterface');
		$factory = new Factory($translator);
		$validator = $factory->make(array('foo' => 'bar'), array('baz' => 'boom'));
		$this->assertEquals($translator, $validator->getTranslator());
		$this->assertEquals(array('foo' => 'bar'), $validator->getData());
		$this->assertEquals(array('baz' => array('boom')), $validator->getRules());

		$presence = m::mock('Illuminate\Validation\PresenceVerifierInterface');
		$factory->extend('foo', function() {});
		$factory->extendImplicit('implicit', function() {});
		$factory->setPresenceVerifier($presence);
		$validator = $factory->make(array(), array());
		$this->assertEquals(array('foo' => function() {}, 'implicit' => function() {}), $validator->getExtensions());
		$this->assertEquals($presence, $validator->getPresenceVerifier());
	}


	public function testCustomResolverIsCalled()
	{
		unset($_SERVER['__validator.factory']);
		$translator = m::mock('Symfony\Component\Translation\TranslatorInterface');
		$factory = new Factory($translator);
		$factory->resolver(function($translator, $data, $rules)
		{
			$_SERVER['__validator.factory'] = true;
			return new Illuminate\Validation\Validator($translator, $data, $rules);
		});
		$validator = $factory->make(array('foo' => 'bar'), array('baz' => 'boom'));

		$this->assertTrue($_SERVER['__validator.factory']);
		$this->assertEquals($translator, $validator->getTranslator());
		$this->assertEquals(array('foo' => 'bar'), $validator->getData());
		$this->assertEquals(array('baz' => array('boom')), $validator->getRules());
		unset($_SERVER['__validator.factory']);
	}

}