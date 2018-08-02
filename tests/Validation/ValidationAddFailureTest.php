<?php

namespace Illuminate\Tests\Validation;

use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Validator;

class ValidationAddFailureTest extends TestCase
{
    /**
     * Making Validator using ValidationValidatorTest.
     *
     * @return Validator
     */
    public function makeValidator(): Validator
    {
        $mainTest = new ValidationValidatorTest();
        $trans = $mainTest->getIlluminateArrayTranslator();
        $v = new Validator($trans, ['foo' => ['bar' => ['baz' => '']]], ['foo.bar.baz' => 'sometimes|required']);

        return $v;
    }

    /**
     * Assert that a method has public access.
     *
     * @param string $class name of the class
     * @param string $method name of the method
     * @throws ReflectionException if $class or $method don't exist
     * @throws PHPUnit_Framework_ExpectationFailedException if the method is not public
     */
    public function assertPublicMethod($class, $method)
    {
        $reflector = new ReflectionMethod($class, $method);
        self::assertTrue($reflector->isPublic(), 'method is not public');
    }

    public function testAddFailureExistsAndVisibile()
    {
        $validator = $this->makeValidator();
        $method_name = 'addFailure';
        $this->assertTrue(method_exists($validator, $method_name));
        $this->assertTrue(is_callable([$validator, $method_name]));
        $this->assertPublicMethod($validator, $method_name);
    }

    public function testAddFailureIsFunctional()
    {
        $attribute = 'Eugene';
        $validator = $this->makeValidator();
        $validator->addFailure($attribute, 'not_in');
        $messages = json_decode($validator->messages());
        $this->assertSame($messages->{'foo.bar.baz'}[0], 'validation.required', 'initial data in messages is lost');
        $this->assertSame($messages->{$attribute}[0], 'validation.not_in', 'new data in messages was not added');
    }
}
