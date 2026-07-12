<?php

namespace Illuminate\Tests\Testing\Exceptions;

use Illuminate\Testing\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;

class InvalidArgumentExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfPHPUnitException()
    {
        $exception = InvalidArgumentException::create(1, 'array');

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testCreateUsesAArticleForConsonantSoundingType()
    {
        $exception = InvalidArgumentException::create(1, 'string');

        $this->assertSame(
            self::class.'::testCreateUsesAArticleForConsonantSoundingType() must be a string',
            $this->messageWithoutArgumentPrefix($exception)
        );
    }

    public function testCreateUsesAnArticleForVowelSoundingType()
    {
        $exception = InvalidArgumentException::create(2, 'array or ArrayAccess');

        $this->assertSame(
            self::class.'::testCreateUsesAnArticleForVowelSoundingType() must be an array or ArrayAccess',
            $this->messageWithoutArgumentPrefix($exception)
        );
    }

    public function testCreateIncludesArgumentNumber()
    {
        $exception = InvalidArgumentException::create(3, 'string');

        $this->assertStringStartsWith('Argument #3 of ', $exception->getMessage());
    }

    protected function messageWithoutArgumentPrefix(InvalidArgumentException $exception): string
    {
        return preg_replace('/^Argument #\d+ of /', '', $exception->getMessage());
    }
}
