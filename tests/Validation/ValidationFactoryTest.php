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
		$validator = $factory->make(['foo' => 'bar'], ['baz' => 'boom']);
		$this->assertEquals($translator, $validator->getTranslator());
		$this->assertEquals(['foo' => 'bar'], $validator->getData());
		$this->assertEquals(['baz' => ['boom']], $validator->getRules());

		$presence = m::mock('Illuminate\Validation\PresenceVerifierInterface');
		$noop1 = function() {};
		$noop2 = function() {};
		$noop3 = function() {};
		$factory->extend('foo', $noop1);
		$factory->extendImplicit('implicit', $noop2);
		$factory->replacer('replacer', $noop3);
		$factory->setPresenceVerifier($presence);
		$validator = $factory->make([], []);
		$this->assertEquals(['foo' => $noop1, 'implicit' => $noop2], $validator->getExtensions());
		$this->assertEquals(['replacer' => $noop3], $validator->getReplacers());
		$this->assertEquals($presence, $validator->getPresenceVerifier());

		$presence = m::mock('Illuminate\Validation\PresenceVerifierInterface');
		$factory->extend('foo', $noop1, 'foo!');
		$factory->extendImplicit('implicit', $noop2, 'implicit!');
		$factory->setPresenceVerifier($presence);
		$validator = $factory->make([], []);
		$this->assertEquals(['foo' => $noop1, 'implicit' => $noop2], $validator->getExtensions());
		$this->assertEquals(['foo' => 'foo!', 'implicit' => 'implicit!'], $validator->getFallbackMessages());
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
		$validator = $factory->make(['foo' => 'bar'], ['baz' => 'boom']);

		$this->assertTrue($_SERVER['__validator.factory']);
		$this->assertEquals($translator, $validator->getTranslator());
		$this->assertEquals(['foo' => 'bar'], $validator->getData());
		$this->assertEquals(['baz' => ['boom']], $validator->getRules());
		unset($_SERVER['__validator.factory']);
	}

}
