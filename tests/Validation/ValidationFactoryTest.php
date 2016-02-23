<?php

use Mockery as m;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;
use Illuminate\Validation\PresenceVerifierInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ValidationFactoryTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testMakeMethodCreatesValidValidator()
    {
        $translator = m::mock(TranslatorInterface::class);
        $factory = new Factory($translator);
        $validator = $factory->make(['foo' => 'bar'], ['baz' => 'boom']);
        $this->assertEquals($translator, $validator->getTranslator());
        $this->assertEquals(['foo' => 'bar'], $validator->getData());
        $this->assertEquals(['baz' => ['boom']], $validator->getRules());

        $presence = m::mock(PresenceVerifierInterface::class);
        $noop1 = function () {};
        $noop2 = function () {};
        $noop3 = function () {};
        $factory->extend('foo', $noop1);
        $factory->extendImplicit('implicit', $noop2);
        $factory->replacer('replacer', $noop3);
        $factory->setPresenceVerifier($presence);
        $validator = $factory->make([], []);
        $this->assertEquals(['foo' => $noop1, 'implicit' => $noop2], $validator->getExtensions());
        $this->assertEquals(['replacer' => $noop3], $validator->getReplacers());
        $this->assertEquals($presence, $validator->getPresenceVerifier());

        $presence = m::mock(PresenceVerifierInterface::class);
        $factory->extend('foo', $noop1, 'foo!');
        $factory->extendImplicit('implicit', $noop2, 'implicit!');
        $factory->setPresenceVerifier($presence);
        $validator = $factory->make([], []);
        $this->assertEquals(['foo' => $noop1, 'implicit' => $noop2], $validator->getExtensions());
        $this->assertEquals(['foo' => 'foo!', 'implicit' => 'implicit!'], $validator->getFallbackMessages());
        $this->assertEquals($presence, $validator->getPresenceVerifier());
    }

    public function testValidateCallsValidateOnTheValidator()
    {
        $validator = m::mock(Validator::class);
        $translator = m::mock(TranslatorInterface::class);
        $factory = m::mock(Factory::class.'[make]', [$translator]);

        $factory->shouldReceive('make')->once()
                ->with(['foo' => 'bar'], ['foo' => 'required'], [], [])
                ->andReturn($validator);

        $validator->shouldReceive('validate')->once();

        $factory->validate(['foo' => 'bar'], ['foo' => 'required']);
    }

    public function testCustomResolverIsCalled()
    {
        unset($_SERVER['__validator.factory']);
        $translator = m::mock(TranslatorInterface::class);
        $factory = new Factory($translator);
        $factory->resolver(function ($translator, $data, $rules) {
            $_SERVER['__validator.factory'] = true;

            return new Validator($translator, $data, $rules);
        });
        $validator = $factory->make(['foo' => 'bar'], ['baz' => 'boom']);

        $this->assertTrue($_SERVER['__validator.factory']);
        $this->assertEquals($translator, $validator->getTranslator());
        $this->assertEquals(['foo' => 'bar'], $validator->getData());
        $this->assertEquals(['baz' => ['boom']], $validator->getRules());
        unset($_SERVER['__validator.factory']);
    }
}
