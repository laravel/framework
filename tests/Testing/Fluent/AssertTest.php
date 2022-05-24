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

    public function testAssertHasCountItemsInProp()
    {
        $assert = AssertableJson::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $assert->has('bar', 2);
    }

    public function testAssertHasCountFailsWhenAmountOfItemsDoesNotMatch()
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

    public function testAssertHasCountFailsWhenPropMissing()
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

    public function testAssertHasOnlyCounts()
    {
        $assert = AssertableJson::fromArray([
            'foo',
            'bar',
            'baz',
        ]);

        $assert->has(3);
    }

    public function testAssertHasOnlyCountFails()
    {
        $assert = AssertableJson::fromArray([
            'foo',
            'bar',
            'baz',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Root level does not have the expected size.');

        $assert->has(2);
    }

    public function testAssertHasOnlyCountFailsScoped()
    {
        $assert = AssertableJson::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [bar] does not have the expected size.');

        $assert->has('bar', function ($bar) {
            $bar->has(3);
        });
    }

    public function testAssertCount()
    {
        $assert = AssertableJson::fromArray([
            'foo',
            'bar',
            'baz',
        ]);

        $assert->count(3);
    }

    public function testAssertCountFails()
    {
        $assert = AssertableJson::fromArray([
            'foo',
            'bar',
            'baz',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Root level does not have the expected size.');

        $assert->count(2);
    }

    public function testAssertCountFailsScoped()
    {
        $assert = AssertableJson::fromArray([
            'bar' => [
                'baz' => 'example',
                'prop' => 'value',
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [bar] does not have the expected size.');

        $assert->has('bar', function ($bar) {
            $bar->count(3);
        });
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
            'data' => [
                'status' => 200,
                'user' => [
                    'id' => 1,
                    'name' => 'Taylor',
                ],
            ],
        ]);

        $assert->where('data', [
            'user' => [
                'name' => 'Taylor',
                'id' => 1,
            ],
            'status' => 200,
        ]);
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

    public function testAssertWhereContainsFailsWithEmptyValue()
    {
        $assert = AssertableJson::fromArray([]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [foo] does not contain [1].');

        $assert->whereContains('foo', ['1']);
    }

    public function testAssertWhereContainsFailsWithMissingValue()
    {
        $assert = AssertableJson::fromArray([
            'foo' => ['bar', 'baz'],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [foo] does not contain [invalid].');

        $assert->whereContains('foo', ['bar', 'baz', 'invalid']);
    }

    public function testAssertWhereContainsFailsWithMissingNestedValue()
    {
        $assert = AssertableJson::fromArray([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [id] does not contain [5].');

        $assert->whereContains('id', [1, 2, 3, 4, 5]);
    }

    public function testAssertWhereContainsFailsWhenDoesNotMatchType()
    {
        $assert = AssertableJson::fromArray([
            'foo' => [1, 2, 3, 4],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [foo] does not contain [1].');

        $assert->whereContains('foo', ['1']);
    }

    public function testAssertWhereContainsFailsWhenDoesNotSatisfyClosure()
    {
        $assert = AssertableJson::fromArray([
            'foo' => [1, 2, 3, 4],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [foo] does not contain a value that passes the truth test within the given closure.');

        $assert->whereContains('foo', [function ($actual) {
            return $actual === 5;
        }]);
    }

    public function testAssertWhereContainsFailsWhenHavingExpectedValueButDoesNotSatisfyClosure()
    {
        $assert = AssertableJson::fromArray([
            'foo' => [1, 2, 3, 4],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [foo] does not contain a value that passes the truth test within the given closure.');

        $assert->whereContains('foo', [1, function ($actual) {
            return $actual === 5;
        }]);
    }

    public function testAssertWhereContainsFailsWhenSatisfiesClosureButDoesNotHaveExpectedValue()
    {
        $assert = AssertableJson::fromArray([
            'foo' => [1, 2, 3, 4],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [foo] does not contain [5].');

        $assert->whereContains('foo', [5, function ($actual) {
            return $actual === 1;
        }]);
    }

    public function testAssertWhereContainsWithNestedValue()
    {
        $assert = AssertableJson::fromArray([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4],
        ]);

        $assert->whereContains('id', 1);
        $assert->whereContains('id', [1, 2, 3, 4]);
        $assert->whereContains('id', [4, 3, 2, 1]);
    }

    public function testAssertWhereContainsWithMatchingType()
    {
        $assert = AssertableJson::fromArray([
            'foo' => [1, 2, 3, 4],
        ]);

        $assert->whereContains('foo', 1);
        $assert->whereContains('foo', [1]);
    }

    public function testAssertWhereContainsWithNullValue()
    {
        $assert = AssertableJson::fromArray([
            'foo' => null,
        ]);

        $assert->whereContains('foo', null);
        $assert->whereContains('foo', [null]);
    }

    public function testAssertWhereContainsWithOutOfOrderMatchingType()
    {
        $assert = AssertableJson::fromArray([
            'foo' => [4, 1, 7, 3],
        ]);

        $assert->whereContains('foo', [1, 7, 4, 3]);
    }

    public function testAssertWhereContainsWithOutOfOrderNestedMatchingType()
    {
        $assert = AssertableJson::fromArray([
            ['bar' => 5],
            ['baz' => 4],
            ['zal' => 8],
        ]);

        $assert->whereContains('baz', 4);
    }

    public function testAssertWhereContainsWithClosure()
    {
        $assert = AssertableJson::fromArray([
            'foo' => [1, 2, 3, 4],
        ]);

        $assert->whereContains('foo', function ($actual) {
            return $actual % 3 === 0;
        });
    }

    public function testAssertWhereContainsWithNestedClosure()
    {
        $assert = AssertableJson::fromArray([
            'foo' => 1,
            'bar' => 2,
            'baz' => 3,
        ]);

        $assert->whereContains('baz', function ($actual) {
            return $actual % 3 === 0;
        });
    }

    public function testAssertWhereContainsWithMultipleClosure()
    {
        $assert = AssertableJson::fromArray([
            'foo' => [1, 2, 3, 4],
        ]);

        $assert->whereContains('foo', [
            function ($actual) {
                return $actual % 3 === 0;
            },
            function ($actual) {
                return $actual % 2 === 0;
            },
        ]);
    }

    public function testAssertWhereContainsWithNullExpectation()
    {
        $assert = AssertableJson::fromArray([
            'foo' => 1,
        ]);

        $assert->whereContains('foo', null);
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

    public function testScopeShorthandWithoutCount()
    {
        $assert = AssertableJson::fromArray([
            'bar' => [
                ['key' => 'first'],
                ['key' => 'second'],
            ],
        ]);

        $called = false;
        $assert->has('bar', null, function (AssertableJson $item) use (&$called) {
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
        $this->expectExceptionMessage('Property [bar] does not have the expected size.');

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

    public function testScopeShorthandFailsWhenAssertingEmptyArray()
    {
        $assert = AssertableJson::fromArray([
            'bar' => [],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'Cannot scope directly onto the first element of property [bar] because it is empty.'
        );

        $assert->has('bar', 0, function (AssertableJson $item) {
            $item->where('key', 'first');
        });
    }

    public function testScopeShorthandFailsWhenAssertingEmptyArrayWithoutCount()
    {
        $assert = AssertableJson::fromArray([
            'bar' => [],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(
            'Cannot scope directly onto the first element of property [bar] because it is empty.'
        );

        $assert->has('bar', null, function (AssertableJson $item) {
            $item->where('key', 'first');
        });
    }

    public function testScopeShorthandFailsWhenSecondArgumentUnsupportedType()
    {
        $assert = AssertableJson::fromArray([
            'bar' => [
                ['key' => 'first'],
                ['key' => 'second'],
            ],
        ]);

        $this->expectException(TypeError::class);

        $assert->has('bar', 'invalid', function (AssertableJson $item) {
            $item->where('key', 'first');
        });
    }

    public function testFirstScope()
    {
        $assert = AssertableJson::fromArray([
            'foo' => [
                'key' => 'first',
            ],
            'bar' => [
                'key' => 'second',
            ],
        ]);

        $assert->first(function (AssertableJson $item) {
            $item->where('key', 'first');
        });
    }

    public function testFirstScopeFailsWhenNoProps()
    {
        $assert = AssertableJson::fromArray([]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Cannot scope directly onto the first element of the root level because it is empty.');

        $assert->first(function (AssertableJson $item) {
            //
        });
    }

    public function testFirstNestedScopeFailsWhenNoProps()
    {
        $assert = AssertableJson::fromArray([
            'foo' => [],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Cannot scope directly onto the first element of property [foo] because it is empty.');

        $assert->has('foo', function (AssertableJson $assert) {
            $assert->first(function (AssertableJson $item) {
                //
            });
        });
    }

    public function testFirstScopeFailsWhenPropSingleValue()
    {
        $assert = AssertableJson::fromArray([
            'foo' => 'bar',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [foo] is not scopeable.');

        $assert->first(function (AssertableJson $item) {
            //
        });
    }

    public function testEachScope()
    {
        $assert = AssertableJson::fromArray([
            'foo' => [
                'key' => 'first',
            ],
            'bar' => [
                'key' => 'second',
            ],
        ]);

        $assert->each(function (AssertableJson $item) {
            $item->whereType('key', 'string');
        });
    }

    public function testEachScopeFailsWhenNoProps()
    {
        $assert = AssertableJson::fromArray([]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Cannot scope directly onto each element of the root level because it is empty.');

        $assert->each(function (AssertableJson $item) {
            //
        });
    }

    public function testEachNestedScopeFailsWhenNoProps()
    {
        $assert = AssertableJson::fromArray([
            'foo' => [],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Cannot scope directly onto each element of property [foo] because it is empty.');

        $assert->has('foo', function (AssertableJson $assert) {
            $assert->each(function (AssertableJson $item) {
                //
            });
        });
    }

    public function testEachScopeFailsWhenPropSingleValue()
    {
        $assert = AssertableJson::fromArray([
            'foo' => 'bar',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [foo] is not scopeable.');

        $assert->each(function (AssertableJson $item) {
            //
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

    public function testAssertWhereTypeString()
    {
        $assert = AssertableJson::fromArray([
            'foo' => 'bar',
        ]);

        $assert->whereType('foo', 'string');
    }

    public function testAssertWhereTypeInteger()
    {
        $assert = AssertableJson::fromArray([
            'foo' => 123,
        ]);

        $assert->whereType('foo', 'integer');
    }

    public function testAssertWhereTypeBoolean()
    {
        $assert = AssertableJson::fromArray([
            'foo' => true,
        ]);

        $assert->whereType('foo', 'boolean');
    }

    public function testAssertWhereTypeDouble()
    {
        $assert = AssertableJson::fromArray([
            'foo' => 12.3,
        ]);

        $assert->whereType('foo', 'double');
    }

    public function testAssertWhereTypeArray()
    {
        $assert = AssertableJson::fromArray([
            'foo' => ['bar', 'baz'],
            'bar' => ['foo' => 'baz'],
        ]);

        $assert->whereType('foo', 'array');
        $assert->whereType('bar', 'array');
    }

    public function testAssertWhereTypeNull()
    {
        $assert = AssertableJson::fromArray([
            'foo' => null,
        ]);

        $assert->whereType('foo', 'null');
    }

    public function testAssertWhereAllType()
    {
        $assert = AssertableJson::fromArray([
            'one' => 'foo',
            'two' => 123,
            'three' => true,
            'four' => 12.3,
            'five' => ['foo', 'bar'],
            'six' => ['foo' => 'bar'],
            'seven' => null,
        ]);

        $assert->whereAllType([
            'one' => 'string',
            'two' => 'integer',
            'three' => 'boolean',
            'four' => 'double',
            'five' => 'array',
            'six' => 'array',
            'seven' => 'null',
        ]);
    }

    public function testAssertWhereTypeWhenWrongTypeIsGiven()
    {
        $assert = AssertableJson::fromArray([
            'foo' => 'bar',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [foo] is not of expected type [integer].');

        $assert->whereType('foo', 'integer');
    }

    public function testAssertWhereTypeWithUnionTypes()
    {
        $firstAssert = AssertableJson::fromArray([
            'foo' => 'bar',
        ]);

        $secondAssert = AssertableJson::fromArray([
            'foo' => null,
        ]);

        $firstAssert->whereType('foo', ['string', 'null']);
        $secondAssert->whereType('foo', ['string', 'null']);
    }

    public function testAssertWhereTypeWhenWrongUnionTypeIsGiven()
    {
        $assert = AssertableJson::fromArray([
            'foo' => 123,
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [foo] is not of expected type [string|null].');

        $assert->whereType('foo', ['string', 'null']);
    }

    public function testAssertWhereTypeWithPipeInUnionType()
    {
        $assert = AssertableJson::fromArray([
            'foo' => 'bar',
        ]);

        $assert->whereType('foo', 'string|null');
    }

    public function testAssertWhereTypeWithPipeInWrongUnionType()
    {
        $assert = AssertableJson::fromArray([
            'foo' => 'bar',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Property [foo] is not of expected type [integer|null].');

        $assert->whereType('foo', 'integer|null');
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
