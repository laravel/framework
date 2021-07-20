<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationArrayRuleTest extends TestCase
{
    public function testSimpleArray()
    {
        $rule = Rule::array();

        /*
         * FAIL: Passing string
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => 'abc',
        ]);

        $this->assertFailing($validator, [
            'my_array' => [
                'validation.array',
            ],
        ]);

        /*
         * FAIL: Passing number
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => 123,
        ]);

        $this->assertFailing($validator, [
            'my_array' => [
                'validation.array',
            ],
        ]);

        /*
         * FAIL: Passing null
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => null,
        ]);

        $this->assertFailing($validator, [
            'my_array' => [
                'validation.array',
            ],
        ]);

        /*
         * PASS: Passing sequential array
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'foo',
                'bar',
            ],
        ]);

        $this->assertPassing($validator, [
            'my_array' => [
                'foo',
                'bar',
            ],
        ]);

        /*
         * FAIL: Passing associative array
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'foo' => '123',
                'bar' => 456,
            ],
        ]);

        $this->assertPassing($validator, [
            'my_array' => [
                'foo' => '123',
                'bar' => 456,
            ],
        ]);
    }

    public function testMinMax()
    {
        $rule = Rule::array()->min(2)->max(4);

        /*
         * FAIL: Passing 1 element
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'abc',
            ],
        ]);

        $this->assertFailing($validator, [
            'my_array' => [
                'validation.min.array',
            ],
        ]);

        /*
         * PASS: Passing sequential array with 2 elements
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'foo',
                'bar',
            ],
        ]);

        $this->assertPassing($validator, [
            'my_array' => [
                'foo',
                'bar',
            ],
        ]);

        /*
         * PASS: Passing associative array with 4 elements
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'foo' => '123',
                'bar' => 456,
                'abc' => '567',
                'def' => 890,
            ],
        ]);

        $this->assertPassing($validator, [
            'my_array' => [
                'foo' => '123',
                'bar' => 456,
                'abc' => '567',
                'def' => 890,
            ],
        ]);

        /*
         * FAIL: Passing array with 5 elements
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'foo' => '123',
                'bar' => 456,
                'third' => 3,
                'fourth' => 4,
                'fifth' => 'fail',
            ],
        ]);

        $this->assertFailing($validator, [
            'my_array' => [
                'validation.max.array',
            ],
        ]);
    }

    public function testSize()
    {
        $rule = Rule::array()->size(3);

        /*
         * FAIL: Passing 2 element
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'abc',
                123,
            ],
        ]);

        $this->assertFailing($validator, [
            'my_array' => [
                'validation.size.array',
            ],
        ]);

        /*
         * PASS: Passing sequential array with 3 elements
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'foo',
                'bar',
                'pass',
            ],
        ]);

        $this->assertPassing($validator, [
            'my_array' => [
                'foo',
                'bar',
                'pass',
            ],
        ]);

        /*
         * PASS: Passing associative array with 3 elements
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'foo' => '123',
                'bar' => 456,
                'abc' => '567',
            ],
        ]);

        $this->assertPassing($validator, [
            'my_array' => [
                'foo' => '123',
                'bar' => 456,
                'abc' => '567',
            ],
        ]);

        /*
         * FAIL: Passing array with 4 elements
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'foo' => '123',
                'bar' => 456,
                'third' => 3,
                'fourth' => 4,
                'fifth' => 'fail',
            ],
        ]);

        $this->assertFailing($validator, [
            'my_array' => [
                'validation.size.array',
            ],
        ]);
    }

    public function testKeyValidation()
    {
        $rule = Rule::array()->keyRules('string', 'size:3');

        /*
         * FAIL: Passing keys that are too long
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'abcd' => 'bar',
                'test' => 'foo',
            ],
        ]);

        $this->assertFailing($validator, [
            'my_array' => [
                'validation.size.string',
            ],
        ]);

        /*
         * FAIL: Passing sequential array (numeric keys)
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'foo',
                'bar',
            ],
        ]);

        $this->assertFailing($validator, [
            'my_array' => [
                'validation.string',
                'validation.size.string',
            ],
        ]);

        /*
         * PASS: Passing associative array with 3-char keys
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'foo' => 'bar',
                'abc' => 'success',
            ],
        ]);

        $this->assertPassing($validator, [
            'my_array' => [
                'foo' => 'bar',
                'abc' => 'success',
            ],
        ]);
    }

    public function testExcludeUnvalidatedKeys()
    {
        $rule = Rule::array()->excludeUnvalidatedKeys();

        /*
         * PASS: Dropping unvalidated keys
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
            'my_array.foo' => 'string',
            'my_array.bar' => 'numeric',
        ], [
            'my_array' => [
                'foo' => 'bar',
                'bar' => 123,
                'unvalidated' => 'missing',
            ],
        ]);

        $this->assertPassing($validator, [
            'my_array' => [
                'foo' => 'bar',
                'bar' => 123,
            ],
        ]);
    }

    public function testIncludeUnvalidatedKeys()
    {
        $rule = Rule::array()->includeUnvalidatedKeys();

        /*
         * PASS: Including unvalidated keys on my_array (with custom rule) and
         *       dropping them on other_array using validator's default.
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
            'my_array.foo' => 'string',
            'my_array.bar' => 'numeric',
            'other_array' => 'array',
            'other_array.name' => 'string',
        ], [
            'my_array' => [
                'foo' => 'bar',
                'bar' => 123,
                'unvalidated' => 'present',
            ],
            'other_array' => [
                'name' => 'Laravel',
                'unvalidated' => 'missing',
            ],
        ]);
        $validator->excludeUnvalidatedArrayKeys = true;

        $this->assertPassing($validator, [
            'my_array' => [
                'foo' => 'bar',
                'bar' => 123,
                'unvalidated' => 'present',
            ],
            'other_array' => [
                'name' => 'Laravel',
            ],
        ]);
    }

    public function testKeysIn()
    {
        $rule = Rule::array()->keysIn('foo', 'bar');

        /*
         * FAIL: Array with unknown keys
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'foo' => 'bar',
                'bar' => 'foo',
                'fail' => 'fail',
            ],
        ]);

        $this->assertFailing($validator, [
            'my_array' => [
                'validation.array',
            ],
        ]);

        /*
         * PASS: Array with only known keys
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'foo' => 'bar',
                'bar' => 'foo',
            ],
        ]);

        $this->assertPassing($validator, [
            'my_array' => [
                'foo' => 'bar',
                'bar' => 'foo',
            ],
        ]);
    }

    public function testKeysNotIn()
    {
        $rule = Rule::array()->keysNotIn('fail');

        /*
         * FAIL: Array with forbidden keys
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'foo' => 'bar',
                'bar' => 'foo',
                'fail' => 'fail',
            ],
        ]);

        $this->assertFailing($validator, [
            'my_array' => [
                'validation.array',
            ],
        ]);

        /*
         * PASS: Array without forbidden keys
         */
        $validator = $this->validator([
            'my_array' => clone $rule,
        ], [
            'my_array' => [
                'foo' => 'bar',
                'bar' => 'foo',
            ],
        ]);

        $this->assertPassing($validator, [
            'my_array' => [
                'foo' => 'bar',
                'bar' => 'foo',
            ],
        ]);
    }

    protected function assertPassing($validator, $values)
    {
        $this->assertTrue($validator->passes());
        $this->assertEqualsCanonicalizing($values, $validator->validated());
    }

    protected function assertFailing($validator, $messages)
    {
        $this->assertFalse($validator->passes());
        $this->assertEqualsCanonicalizing($messages, $validator->messages()->toArray());
    }

    protected function validator($rules, $data)
    {
        return new Validator(resolve('translator'), $data, $rules);
    }

    protected function setUp(): void
    {
        $container = Container::getInstance();

        $container->bind('translator', function () {
            return new Translator(new ArrayLoader, 'en');
        });

        Facade::setFacadeApplication($container);

        (new ValidationServiceProvider($container))->register();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        Facade::clearResolvedInstances();

        Facade::setFacadeApplication(null);
    }
}
