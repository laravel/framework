<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Contracts\Translation\Translator as TranslatorInterface;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\PresenceVerifierInterface;
use Illuminate\Validation\Validator;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class ValidationFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Validator::flushState();
    }

    public function testMakeMethodCreatesValidValidator()
    {
        $translator = Mockery::mock(TranslatorInterface::class);
        $factory = new Factory($translator);
        $validator = $factory->make(['foo' => 'bar'], ['baz' => 'boom']);
        $this->assertEquals($translator, $validator->getTranslator());
        $this->assertEquals(['foo' => 'bar'], $validator->getData());
        $this->assertEquals(['baz' => ['boom']], $validator->getRules());

        $presence = Mockery::mock(PresenceVerifierInterface::class);
        $noop1 = function () {
            //
        };
        $noop2 = function () {
            //
        };
        $noop3 = function () {
            //
        };
        $factory->extend('foo', $noop1);
        $factory->extendImplicit('implicit', $noop2);
        $factory->extendDependent('dependent', $noop3);
        $factory->replacer('replacer', $noop3);
        $factory->setPresenceVerifier($presence);
        $validator = $factory->make([], []);
        $this->assertEquals(['foo' => $noop1, 'implicit' => $noop2, 'dependent' => $noop3], $validator->extensions);
        $this->assertEquals(['replacer' => $noop3], $validator->replacers);
        $this->assertEquals($presence, $validator->getPresenceVerifier());

        $presence = Mockery::mock(PresenceVerifierInterface::class);
        $factory->extend('foo', $noop1, 'foo!');
        $factory->extendImplicit('implicit', $noop2, 'implicit!');
        $factory->extendImplicit('dependent', $noop3, 'dependent!');
        $factory->setPresenceVerifier($presence);
        $validator = $factory->make([], []);
        $this->assertEquals(['foo' => $noop1, 'implicit' => $noop2, 'dependent' => $noop3], $validator->extensions);
        $this->assertEquals(['foo' => 'foo!', 'implicit' => 'implicit!', 'dependent' => 'dependent!'], $validator->fallbackMessages);
        $this->assertEquals($presence, $validator->getPresenceVerifier());
    }

    public function testValidateCallsValidateOnTheValidator()
    {
        $validator = Mockery::mock(Validator::class);
        $translator = Mockery::mock(TranslatorInterface::class);
        $factory = Mockery::mock(Factory::class.'[make]', [$translator]);

        $factory->expects('make')
            ->with(['foo' => 'bar', 'baz' => 'boom'], ['foo' => 'required'], [], [])
            ->andReturn($validator);

        $validator->expects('validate')->andReturn(['foo' => 'bar']);

        $validated = $factory->validate(
            ['foo' => 'bar', 'baz' => 'boom'],
            ['foo' => 'required']
        );

        $this->assertEquals(['foo' => 'bar'], $validated);
    }

    public function testCustomResolverIsCalled()
    {
        unset($_SERVER['__validator.factory']);
        $translator = Mockery::mock(TranslatorInterface::class);
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

    public function testValidateMethodCanBeCalledPublicly()
    {
        $translator = Mockery::mock(TranslatorInterface::class);
        $factory = new Factory($translator);
        $factory->extend('foo', function ($attribute, $value, $parameters, $validator) {
            return $validator->validateArray($attribute, $value);
        });

        $validator = $factory->make(['bar' => ['baz']], ['bar' => 'foo']);
        $this->assertTrue($validator->passes());
    }

    public function testExcludeAndIncludeUnvalidatedArrayKeys()
    {
        $translator = Mockery::mock(TranslatorInterface::class);

        $factory = new Factory($translator);
        // check the default behaviour.
        $validator1 = $factory->make(['key' => ['val']], ['key' => 'required']);
        $this->assertTrue($validator1->excludeUnvalidatedArrayKeys);

        $factory->excludeUnvalidatedArrayKeys();
        $validator2 = $factory->make(['key' => ['val']], ['key' => 'required']);
        $this->assertTrue($validator2->excludeUnvalidatedArrayKeys);

        $factory->includeUnvalidatedArrayKeys();
        $validator3 = $factory->make(['key' => ['val']], ['key' => 'required']);
        $this->assertFalse($validator3->excludeUnvalidatedArrayKeys);

        // checks it does not switch behaviour automatically.
        $validator4 = $factory->make(['key' => ['val']], ['key' => 'required']);
        $this->assertFalse($validator4->excludeUnvalidatedArrayKeys);

        // checks it can switch.
        $factory->excludeUnvalidatedArrayKeys();
        $validator5 = $factory->make(['key' => ['val']], ['key' => 'required']);
        $this->assertTrue($validator5->excludeUnvalidatedArrayKeys);

        // checks switching does not affect previously created validator objects.
        $this->assertTrue($validator1->excludeUnvalidatedArrayKeys);
        $this->assertTrue($validator2->excludeUnvalidatedArrayKeys);
        $this->assertFalse($validator3->excludeUnvalidatedArrayKeys);
        $this->assertFalse($validator4->excludeUnvalidatedArrayKeys);
    }

    public function testSetContainer()
    {
        $translator = Mockery::mock(TranslatorInterface::class);
        $container = new Container;
        $factory = new Factory($translator);

        $this->assertNull($factory->getContainer());

        $this->assertSame($container, $factory->setContainer($container)->getContainer());
    }

    public function testMessagePluralizationOnlyAffectsNewValidatorsFromTheConfiguredFactory()
    {
        $message = '{1} There is one|[2,*] There are :count';
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines(['validation.min.array' => $message], 'en');

        $factory = new Factory($translator);
        $default = $factory->make(['items' => ['a', 'b']], ['items' => 'array|min:3']);

        $factory->pluralizeMessages();
        $enabled = $factory->make(['items' => ['a', 'b']], ['items' => 'array|min:3']);

        $other = (new Factory($translator))->make(['items' => ['a', 'b']], ['items' => 'array|min:3']);

        $factory->pluralizeMessages(false);
        $disabled = $factory->make(['items' => ['a', 'b']], ['items' => 'array|min:3']);

        $this->assertSame($message, $default->errors()->first('items'));
        $this->assertSame('There are 2', $enabled->errors()->first('items'));
        $this->assertSame($message, $other->errors()->first('items'));
        $this->assertSame($message, $disabled->errors()->first('items'));
    }

    public function testMessagePluralizationCanBeDisabledForAnIndividualFactoryValidator()
    {
        $message = '{1} There is one|[2,*] There are :count';
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines(['validation.min.array' => $message], 'en');

        $factory = new Factory($translator);
        $factory->pluralizeMessages();

        $disabled = $factory->make(['items' => ['a', 'b']], ['items' => 'array|min:3'])->pluralizeMessages(false);
        $enabled = $factory->make(['items' => ['a', 'b']], ['items' => 'array|min:3']);

        $this->assertSame($message, $disabled->errors()->first('items'));
        $this->assertSame('There are 2', $enabled->errors()->first('items'));
    }

    public function testMessagePluralizationWorksWithCustomResolvers()
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines(['validation.min.array' => '{1} There is one|[2,*] There are :count'], 'en');

        $factory = new Factory($translator);
        $factory->resolver(fn ($translator, $data, $rules, $messages, $attributes) => new Validator($translator, $data, $rules, $messages, $attributes));
        $factory->pluralizeMessages();

        $validator = $factory->make(['items' => ['a', 'b']], ['items' => 'array|min:3']);

        $this->assertSame('There are 2', $validator->errors()->first('items'));
    }

    public function testFakeDnsLookupsDelegatesToTheValidator()
    {
        (new Factory(Mockery::mock(TranslatorInterface::class)))->fakeDnsLookups();

        $this->assertTrue((new ReflectionProperty(Validator::class, 'fakeDnsLookups'))->getValue());
    }
}
