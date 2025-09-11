<?php

namespace Illuminate\Tests\Testing;

use Illuminate\Testing\Assert;
use Illuminate\Testing\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use stdClass;

class AssertTest extends TestCase
{
    public function testArraySubset()
    {
        Assert::assertArraySubset([
            'string' => 'string',
            'object' => new stdClass(),
        ], [
            'int' => 1,
            'string' => 'string',
            'object' => new stdClass(),
        ]);
    }

    public function testArraySubsetMayFail(): void
    {
        $this->expectException(ExpectationFailedException::class);

        Assert::assertArraySubset([
            'int' => 2,
            'string' => 'string',
            'object' => new stdClass(),
        ], [
            'int' => 1,
            'string' => 'string',
            'object' => new stdClass(),
        ]);
    }

    public function testArraySubsetWithStrict(): void
    {
        Assert::assertArraySubset([
            'string' => 'string',
            'object' => $object = new stdClass(),
        ], [
            'int' => 1,
            'string' => 'string',
            'object' => $object,
        ], true);
    }

    public function testArraySubsetWithStrictMayFail(): void
    {
        $this->expectException(ExpectationFailedException::class);

        Assert::assertArraySubset([
            'string' => 'string',
            'object' => new stdClass(),
        ], [
            'int' => 1,
            'string' => 'string',
            'object' => new stdClass(),
        ], true);
    }

    public function testArraySubsetMayFailIfArrayIsNotArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Argument #1 of Illuminate\Testing\Assert::assertArraySubset() must be an array or ArrayAccess'
        );

        Assert::assertArraySubset('string', [
            'int' => 1,
            'string' => 'string',
            'object' => new stdClass(),
        ]);
    }

    public function testArraySubsetMayFailIfSubsetIsNotArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Argument #2 of Illuminate\Testing\Assert::assertArraySubset() must be an array or ArrayAccess'
        );

        Assert::assertArraySubset([
            'string' => 'string',
            'object' => new stdClass(),
        ], 'string');
    }
}
