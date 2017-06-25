<?php

namespace Illuminate\Tests\Validation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Factory;
use Illuminate\Contracts\Translation\Translator as TranslatorInterface;
use Illuminate\Validation\FailureFormatters\Rule as RuleFailureFormatter;
use Illuminate\Validation\FailureFormatters\Message as MessageFailureFormatter;

class ValidationFailureFormatterTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testFailureFormattedAsMessage()
    {
        $translator = m::mock(TranslatorInterface::class);
        $factory = new Factory($translator);
        $validator = $factory->make(
            ['foo' => 'bar'],
            ['foo' => 'integer'],
            ['integer' => 'Value should be of an integer type.']
        );

        $translator->shouldReceive('trans')
                   ->andReturn('Value should be of an integer type.');

        $this->assertFalse($validator->passes());
        $this->assertEquals(
            ['foo' => ['Value should be of an integer type.']],
            $validator->messages()->toArray()
        );
    }

    public function testFailureFormattedAsRule()
    {
        $translator = m::mock(TranslatorInterface::class);
        $factory = new Factory($translator);
        $factory->setFailureFormatter(new RuleFailureFormatter);
        $validator = $factory->make(
            ['foo' => 'bar'],
            ['foo' => 'integer'],
            ['integer' => 'Value should be of an integer type.']
        );

        $translator->shouldNotHaveReceived('trans');

        $this->assertFalse($validator->passes());
        $this->assertEquals(
            ['foo' => ['integer']],
            $validator->messages()->toArray()
        );
    }

    public function testFailureFormattedAsMessageByValidatorOverwrite()
    {
        $translator = m::mock(TranslatorInterface::class);
        $factory = new Factory($translator);
        $factory->setFailureFormatter(new RuleFailureFormatter);
        $validator = $factory->make(
            ['foo' => 'bar'],
            ['foo' => 'integer'],
            ['integer' => 'Value should be of an integer type.']
        );

        // overwriting Factory set Failure Formatter
        $validator->setFailureFormatter(new MessageFailureFormatter);

        $translator->shouldReceive('trans')
                   ->andReturn('Value should be of an integer type.');

        $this->assertFalse($validator->passes());
        $this->assertEquals(
            ['foo' => ['Value should be of an integer type.']],
            $validator->messages()->toArray()
        );
    }

}