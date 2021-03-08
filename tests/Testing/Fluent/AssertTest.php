<?php

namespace Illuminate\Tests\Testing\Fluent;

use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Tests\Testing\Stubs\ArrayableStubObject;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;

class AssertTest extends TestCase
{
    public function testAssertHas()
    {
        $assert = AssertableJson::fromArray([
            'prop' => 'value',
        ]);

        $assert->has('prop');
    }

    public function testAssertHasFailsWhenPropMissing()
    {
        $assert = AssertableJson::fromArray([
            'bar' => 'value',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [prop] does not exist.');

        $assert->has('prop');
    }

    public function testAssertHasNestedProp()
    {
        $assert = AssertableJson::fromArray([
            'example' => [
                'nested' => 'nested-value',
            ],
        ]);

        $assert->has('example.nested');
    }

    public function testAssertHasFailsWhenNestedPropMissing()
    {
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $assert->has('bar', 2);
    }

    public function testAssertCountFailsWhenAmountOfItemsDoesNotMatch()
    {
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
            'bar' => 'baz',
        ]);

        $this->expectException(TypeError::class);

        $assert->has('bar', 'invalid');
    }

    public function testAssertMissing()
    {
        $assert = AssertableJson::fromArray([
            'foo' => [
                'bar' => true,
            ],
        ]);

        $assert->missing('foo.baz');
    }

    public function testAssertMissingFailsWhenPropExists()
    {
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
            'baz' => 'foo',
        ]);

        $assert->missingAll([
            'foo',
            'bar',
        ]);
    }

    public function testAssertMissingAllFailsWhenAtLeastOnePropExists()
    {
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
            'baz' => 'foo',
        ]);

        $assert->missingAll('foo', 'bar');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [baz] was found while it was expected to be missing.');

        $assert->missingAll('bar', 'baz');
    }

    public function testAssertWhereMatchesValue()
    {
        $assert = AssertableJson::fromArray([
            'bar' => 'value',
        ]);

        $assert->where('bar', 'value');
    }

    public function testAssertWhereFailsWhenDoesNotMatchValue()
    {
        $assert = AssertableJson::fromArray([
            'bar' => 'value',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [bar] does not match the expected value.');

        $assert->where('bar', 'invalid');
    }

    public function testAssertWhereFailsWhenMissing()
    {
        $assert = AssertableJson::fromArray([
            'bar' => 'value',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [baz] does not exist.');

        $assert->where('baz', 'invalid');
    }

    public function testAssertWhereFailsWhenMachingLoosely()
    {
        $assert = AssertableJson::fromArray([
            'bar' => 1,
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [bar] does not match the expected value.');

        $assert->where('bar', true);
    }

    public function testAssertWhereUsingClosure()
    {
        $assert = AssertableJson::fromArray([
            'bar' => 'baz',
        ]);

        $assert->where('bar', function ($value) {
            return $value === 'baz';
        });
    }

    public function testAssertWhereFailsWhenDoesNotMatchValueUsingClosure()
    {
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
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

        $assert = AssertableJson::fromArray([
            'bar' => $stub->toArray(),
        ]);

        $assert->where('bar', $stub);
    }

    public function testAssertWhereMatchesValueUsingArrayableWhenSortedDifferently()
    {
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
            'example' => [
                'nested' => 'nested-value',
            ],
        ]);

        $assert->where('example.nested', 'nested-value');
    }

    public function testAssertNestedWhereFailsWhenDoesNotMatchValue()
    {
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $called = false;
        $assert->has('bar', function (AssertableJson $assert) use (&$called) {
            $called = true;
            $assert
                ->where('baz', 'example')
                ->where('prop', 'value');
        });

        $this->assertTrue($called, 'The scoped query was never actually called.');
    }

    public function testScopeFailsWhenPropMissing()
    {
        $assert = AssertableJson::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [baz] does not exist.');

        $assert->has('baz', function (AssertableJson $item) {
            $item->where('baz', 'example');
        });
    }

    public function testScopeFailsWhenPropSingleValue()
    {
        $assert = AssertableJson::fromArray([
            'bar' => 'value',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [bar] is not scopeable.');

        $assert->has('bar', function (AssertableJson $item) {
            //
        });
    }

    public function testScopeShorthand()
    {
        $assert = AssertableJson::fromArray([
            'bar' => [
                ['key' => 'first'],
                ['key' => 'second'],
            ],
        ]);

        $called = false;
        $assert->has('bar', 2, function (AssertableJson $item) use (&$called) {
            $item->where('key', 'first');
            $called = true;
        });

        $this->assertTrue($called, 'The scoped query was never actually called.');
    }

    public function testScopeShorthandFailsWhenAssertingZeroItems()
    {
        $assert = AssertableJson::fromArray([
            'bar' => [
                ['key' => 'first'],
                ['key' => 'second'],
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Cannot scope directly onto the first entry of property [bar] when asserting that it has a size of 0.');

        $assert->has('bar', 0, function (AssertableJson $item) {
            $item->where('key', 'first');
        });
    }

    public function testScopeShorthandFailsWhenAmountOfItemsDoesNotMatch()
    {
        $assert = AssertableJson::fromArray([
            'bar' => [
                ['key' => 'first'],
                ['key' => 'second'],
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [bar] does not have the expected size.');

        $assert->has('bar', 1, function (AssertableJson $item) {
            $item->where('key', 'first');
        });
    }

    public function testFailsWhenNotInteractingWithAllPropsInScope()
    {
        $assert = AssertableJson::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Unexpected properties were found in scope [bar].');

        $assert->has('bar', function (AssertableJson $item) {
            $item->where('baz', 'example');
        });
    }

    public function testDisableInteractionCheckForCurrentScope()
    {
        $assert = AssertableJson::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $assert->has('bar', function (AssertableJson $item) {
            $item->etc();
        });
    }

    public function testCannotDisableInteractionCheckForDifferentScopes()
    {
        $assert = AssertableJson::fromArray([
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

        $assert->has('bar', function (AssertableJson $item) {
            $item
                ->etc()
                ->has('baz', function (AssertableJson $item) {
                    //
                });
        });
    }

    public function testTopLevelPropInteractionDisabledByDefault()
    {
        $assert = AssertableJson::fromArray([
            'foo' => 'bar',
            'bar' => 'baz',
        ]);

        $assert->has('foo');
    }

    public function testTopLevelInteractionEnabledWhenInteractedFlagSet()
    {
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
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
        $assert = AssertableJson::fromArray([
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
        AssertableJson::macro('myCustomMacro', function () {
            throw new RuntimeException('My Custom Macro was called!');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('My Custom Macro was called!');

        $assert = AssertableJson::fromArray(['foo' => 'bar']);
        $assert->myCustomMacro();
    }

    public function testTappable()
    {
        $assert = AssertableJson::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $called = false;
        $assert->has('bar', function (AssertableJson $assert) use (&$called) {
            $assert->etc();
            $assert->tap(function (AssertableJson $assert) use (&$called) {
                $called = true;
            });
        });

        $this->assertTrue($called, 'The scoped query was never actually called.');
    }
}
