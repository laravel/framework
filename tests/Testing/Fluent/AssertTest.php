<?php

namespace Illuminate\Tests\Testing\Fluent;

use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\Assert;
use Illuminate\Tests\Testing\Stubs\ArrayableStubObject;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;

class AssertTest extends TestCase
{
    public function testAssertHas()
    {
        $assert = Assert::fromArray([
            'prop' => 'value',
        ]);

        $assert->has('prop');
    }

    public function testAssertHasFailsWhenPropMissing()
    {
        $assert = Assert::fromArray([
            'bar' => 'value',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [prop] does not exist.');

        $assert->has('prop');
    }

    public function testAssertHasNestedProp()
    {
        $assert = Assert::fromArray([
            'example' => [
                'nested' => 'nested-value',
            ],
        ]);

        $assert->has('example.nested');
    }

    public function testAssertHasFailsWhenNestedPropMissing()
    {
        $assert = Assert::fromArray([
            'example' => [
                'nested' => 'nested-value',
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [example.another] does not exist.');

        $assert->has('example.another');
    }

    public function testAssertCountItemsInProp()
    {
        $assert = Assert::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $assert->has('bar', 2);
    }

    public function testAssertCountFailsWhenAmountOfItemsDoesNotMatch()
    {
        $assert = Assert::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [bar] does not have the expected size.');

        $assert->has('bar', 1);
    }

    public function testAssertCountFailsWhenPropMissing()
    {
        $assert = Assert::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [baz] does not exist.');

        $assert->has('baz', 1);
    }

    public function testAssertHasFailsWhenSecondArgumentUnsupportedType()
    {
        $assert = Assert::fromArray([
            'bar' => 'baz',
        ]);

        $this->expectException(TypeError::class);

        $assert->has('bar', 'invalid');
    }

    public function testAssertMissing()
    {
        $assert = Assert::fromArray([
            'foo' => [
                'bar' => true,
            ],
        ]);

        $assert->missing('foo.baz');
    }

    public function testAssertMissingFailsWhenPropExists()
    {
        $assert = Assert::fromArray([
            'prop' => 'value',
            'foo' => [
                'bar' => true,
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [foo.bar] was found while it was expected to be missing.');

        $assert->missing('foo.bar');
    }

    public function testAssertMissingAll()
    {
        $assert = Assert::fromArray([
            'baz' => 'foo',
        ]);

        $assert->missingAll([
            'foo',
            'bar',
        ]);
    }

    public function testAssertMissingAllFailsWhenAtLeastOnePropExists()
    {
        $assert = Assert::fromArray([
            'baz' => 'foo',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [baz] was found while it was expected to be missing.');

        $assert->missingAll([
            'bar',
            'baz',
        ]);
    }

    public function testAssertMissingAllAcceptsMultipleArgumentsInsteadOfArray()
    {
        $assert = Assert::fromArray([
            'baz' => 'foo',
        ]);

        $assert->missingAll('foo', 'bar');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [baz] was found while it was expected to be missing.');

        $assert->missingAll('bar', 'baz');
    }

    public function testAssertWhereMatchesValue()
    {
        $assert = Assert::fromArray([
            'bar' => 'value',
        ]);

        $assert->where('bar', 'value');
    }

    public function testAssertWhereFailsWhenDoesNotMatchValue()
    {
        $assert = Assert::fromArray([
            'bar' => 'value',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [bar] does not match the expected value.');

        $assert->where('bar', 'invalid');
    }

    public function testAssertWhereFailsWhenMissing()
    {
        $assert = Assert::fromArray([
            'bar' => 'value',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [baz] does not exist.');

        $assert->where('baz', 'invalid');
    }

    public function testAssertWhereFailsWhenMachingLoosely()
    {
        $assert = Assert::fromArray([
            'bar' => 1,
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [bar] does not match the expected value.');

        $assert->where('bar', true);
    }

    public function testAssertWhereUsingClosure()
    {
        $assert = Assert::fromArray([
            'bar' => 'baz',
        ]);

        $assert->where('bar', function ($value) {
            return $value === 'baz';
        });
    }

    public function testAssertWhereFailsWhenDoesNotMatchValueUsingClosure()
    {
        $assert = Assert::fromArray([
            'bar' => 'baz',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [bar] was marked as invalid using a closure.');

        $assert->where('bar', function ($value) {
            return $value === 'invalid';
        });
    }

    public function testAssertWhereClosureArrayValuesAreAutomaticallyCastedToCollections()
    {
        $assert = Assert::fromArray([
            'bar' => [
                'baz' => 'foo',
                'example' => 'value',
            ],
        ]);

        $assert->where('bar', function ($value) {
            $this->assertInstanceOf(Collection::class, $value);

            return $value->count() === 2;
        });
    }

    public function testAssertWhereMatchesValueUsingArrayable()
    {
        $stub = ArrayableStubObject::make(['foo' => 'bar']);

        $assert = Assert::fromArray([
            'bar' => $stub->toArray(),
        ]);

        $assert->where('bar', $stub);
    }

    public function testAssertWhereMatchesValueUsingArrayableWhenSortedDifferently()
    {
        $assert = Assert::fromArray([
            'bar' => [
                'baz' => 'foo',
                'example' => 'value',
            ],
        ]);

        $assert->where('bar', function ($value) {
            $this->assertInstanceOf(Collection::class, $value);

            return $value->count() === 2;
        });
    }

    public function testAssertWhereFailsWhenDoesNotMatchValueUsingArrayable()
    {
        $assert = Assert::fromArray([
            'bar' => ['id' => 1, 'name' => 'Example'],
            'baz' => [
                'id' => 1,
                'name' => 'Taylor Otwell',
                'email' => 'taylor@laravel.com',
                'email_verified_at' => '2021-01-22T10:34:42.000000Z',
                'created_at' => '2021-01-22T10:34:42.000000Z',
                'updated_at' => '2021-01-22T10:34:42.000000Z',
            ],
        ]);

        $assert
            ->where('bar', ArrayableStubObject::make(['name' => 'Example', 'id' => 1]))
            ->where('baz', [
                'name' => 'Taylor Otwell',
                'email' => 'taylor@laravel.com',
                'id' => 1,
                'email_verified_at' => '2021-01-22T10:34:42.000000Z',
                'updated_at' => '2021-01-22T10:34:42.000000Z',
                'created_at' => '2021-01-22T10:34:42.000000Z',
            ]);
    }

    public function testAssertNestedWhereMatchesValue()
    {
        $assert = Assert::fromArray([
            'example' => [
                'nested' => 'nested-value',
            ],
        ]);

        $assert->where('example.nested', 'nested-value');
    }

    public function testAssertNestedWhereFailsWhenDoesNotMatchValue()
    {
        $assert = Assert::fromArray([
            'example' => [
                'nested' => 'nested-value',
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [example.nested] does not match the expected value.');

        $assert->where('example.nested', 'another-value');
    }

    public function testScope()
    {
        $assert = Assert::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $called = false;
        $assert->has('bar', function (Assert $assert) use (&$called) {
            $called = true;
            $assert
                ->where('baz', 'example')
                ->where('prop', 'value');
        });

        $this->assertTrue($called, 'The scoped query was never actually called.');
    }

    public function testScopeFailsWhenPropMissing()
    {
        $assert = Assert::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [baz] does not exist.');

        $assert->has('baz', function (Assert $item) {
            $item->where('baz', 'example');
        });
    }

    public function testScopeFailsWhenPropSingleValue()
    {
        $assert = Assert::fromArray([
            'bar' => 'value',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [bar] is not scopeable.');

        $assert->has('bar', function (Assert $item) {
            //
        });
    }

    public function testScopeShorthand()
    {
        $assert = Assert::fromArray([
            'bar' => [
                ['key' => 'first'],
                ['key' => 'second'],
            ],
        ]);

        $called = false;
        $assert->has('bar', 2, function (Assert $item) use (&$called) {
            $item->where('key', 'first');
            $called = true;
        });

        $this->assertTrue($called, 'The scoped query was never actually called.');
    }

    public function testScopeShorthandFailsWhenAssertingZeroItems()
    {
        $assert = Assert::fromArray([
            'bar' => [
                ['key' => 'first'],
                ['key' => 'second'],
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Cannot scope directly onto the first entry of property [bar] when asserting that it has a size of 0.');

        $assert->has('bar', 0, function (Assert $item) {
            $item->where('key', 'first');
        });
    }

    public function testScopeShorthandFailsWhenAmountOfItemsDoesNotMatch()
    {
        $assert = Assert::fromArray([
            'bar' => [
                ['key' => 'first'],
                ['key' => 'second'],
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [bar] does not have the expected size.');

        $assert->has('bar', 1, function (Assert $item) {
            $item->where('key', 'first');
        });
    }

    public function testFailsWhenNotInteractingWithAllPropsInScope()
    {
        $assert = Assert::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Unexpected properties were found in scope [bar].');

        $assert->has('bar', function (Assert $item) {
            $item->where('baz', 'example');
        });
    }

    public function testDisableInteractionCheckForCurrentScope()
    {
        $assert = Assert::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $assert->has('bar', function (Assert $item) {
            $item->etc();
        });
    }

    public function testCannotDisableInteractionCheckForDifferentScopes()
    {
        $assert = Assert::fromArray([
            'bar' => [
                'baz' => [
                    'foo' => 'bar',
                    'example' => 'value',
                ],
                'prop' => 'value',
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Unexpected properties were found in scope [bar.baz].');

        $assert->has('bar', function (Assert $item) {
            $item
                ->etc()
                ->has('baz', function (Assert $item) {
                    //
                });
        });
    }

    public function testTopLevelPropInteractionDisabledByDefault()
    {
        $assert = Assert::fromArray([
            'foo' => 'bar',
            'bar' => 'baz',
        ]);

        $assert->has('foo');
    }

    public function testTopLevelInteractionEnabledWhenInteractedFlagSet()
    {
        $assert = Assert::fromArray([
            'foo' => 'bar',
            'bar' => 'baz',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Unexpected properties were found on the root level.');

        $assert
            ->has('foo')
            ->interacted();
    }

    public function testAssertWhereAllMatchesValues()
    {
        $assert = Assert::fromArray([
            'foo' => [
                'bar' => 'value',
                'example' => ['hello' => 'world'],
            ],
            'baz' => 'another',
        ]);

        $assert->whereAll([
            'foo.bar' => 'value',
            'foo.example' => ArrayableStubObject::make(['hello' => 'world']),
            'baz' => function ($value) {
                return $value === 'another';
            },
        ]);
    }

    public function testAssertWhereAllFailsWhenAtLeastOnePropDoesNotMatchValue()
    {
        $assert = Assert::fromArray([
            'foo' => 'bar',
            'baz' => 'example',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [baz] was marked as invalid using a closure.');

        $assert->whereAll([
            'foo' => 'bar',
            'baz' => function ($value) {
                return $value === 'foo';
            },
        ]);
    }

    public function testAssertHasAll()
    {
        $assert = Assert::fromArray([
            'foo' => [
                'bar' => 'value',
                'example' => ['hello' => 'world'],
            ],
            'baz' => 'another',
        ]);

        $assert->hasAll([
            'foo.bar',
            'foo.example',
            'baz',
        ]);
    }

    public function testAssertHasAllFailsWhenAtLeastOnePropMissing()
    {
        $assert = Assert::fromArray([
            'foo' => [
                'bar' => 'value',
                'example' => ['hello' => 'world'],
            ],
            'baz' => 'another',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [foo.baz] does not exist.');

        $assert->hasAll([
            'foo.bar',
            'foo.baz',
            'baz',
        ]);
    }

    public function testAssertHasAllAcceptsMultipleArgumentsInsteadOfArray()
    {
        $assert = Assert::fromArray([
            'foo' => [
                'bar' => 'value',
                'example' => ['hello' => 'world'],
            ],
            'baz' => 'another',
        ]);

        $assert->hasAll('foo.bar', 'foo.example', 'baz');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [foo.baz] does not exist.');

        $assert->hasAll('foo.bar', 'foo.baz', 'baz');
    }

    public function testAssertCountMultipleProps()
    {
        $assert = Assert::fromArray([
            'bar' => [
                'key' => 'value',
                'prop' => 'example',
            ],
            'baz' => [
                'another' => 'value',
            ],
        ]);

        $assert->hasAll([
            'bar' => 2,
            'baz' => 1,
        ]);
    }

    public function testAssertCountMultiplePropsFailsWhenPropMissing()
    {
        $assert = Assert::fromArray([
            'bar' => [
                'key' => 'value',
                'prop' => 'example',
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [baz] does not exist.');

        $assert->hasAll([
            'bar' => 2,
            'baz' => 1,
        ]);
    }

    public function testMacroable()
    {
        Assert::macro('myCustomMacro', function () {
            throw new RuntimeException('My Custom Macro was called!');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('My Custom Macro was called!');

        $assert = Assert::fromArray(['foo' => 'bar']);
        $assert->myCustomMacro();
    }

    public function testTappable()
    {
        $assert = Assert::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $called = false;
        $assert->has('bar', function (Assert $assert) use (&$called) {
            $assert->etc();
            $assert->tap(function (Assert $assert) use (&$called) {
                $called = true;
            });
        });

        $this->assertTrue($called, 'The scoped query was never actually called.');
    }
}
