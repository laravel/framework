<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Exceptions\PasswordInvalidArgumentsException;
use Illuminate\Support\Exceptions\PasswordInvalidLengthArgument;
use Illuminate\Support\Password;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

class SupportPasswordTest extends TestCase
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function dataProviderPasswordCreation(): array
    {
        return [
            'invalid arguments' => [
                'length' => 16,
                'withLetters' => false,
                'withNumbers' => false,
                'withSymbols' => false,
                'withSpaces' => false,
                'exception' => PasswordInvalidArgumentsException::class,
            ],
            'invalid length argument 1' => [
                'length' => 1,
                'withLetters' => true,
                'withNumbers' => true,
                'withSymbols' => false,
                'withSpaces' => false,
                'exception' => PasswordInvalidLengthArgument::class,
            ],
            'invalid length argument 2' => [
                'length' => 2,
                'withLetters' => true,
                'withNumbers' => true,
                'withSymbols' => true,
                'withSpaces' => false,
                'exception' => PasswordInvalidLengthArgument::class,
            ],
            'invalid length argument 3' => [
                'length' => 3,
                'withLetters' => true,
                'withNumbers' => true,
                'withSymbols' => true,
                'withSpaces' => true,
                'exception' => PasswordInvalidLengthArgument::class,
            ],
            'only letters' => [
                'length' => 16,
                'withLetters' => true,
                'withNumbers' => false,
                'withSymbols' => false,
                'withSpaces' => false,
                'exception' => null,
            ],
            'only numbers' => [
                'length' => 16,
                'withLetters' => false,
                'withNumbers' => true,
                'withSymbols' => false,
                'withSpaces' => false,
                'exception' => null,
            ],
            'only symbols' => [
                'length' => 16,
                'withLetters' => false,
                'withNumbers' => false,
                'withSymbols' => true,
                'withSpaces' => false,
                'exception' => null,
            ],
            'only letters with spaces' => [
                'length' => 16,
                'withLetters' => true,
                'withNumbers' => false,
                'withSymbols' => false,
                'withSpaces' => true,
                'exception' => null,
            ],
            'only numbers with spaces' => [
                'length' => 16,
                'withLetters' => false,
                'withNumbers' => true,
                'withSymbols' => false,
                'withSpaces' => true,
                'exception' => null,
            ],
            'only symbols with spaces' => [
                'length' => 16,
                'withLetters' => false,
                'withNumbers' => false,
                'withSymbols' => true,
                'withSpaces' => true,
                'exception' => null,
            ],
            'letters and numbers and symbols' => [
                'length' => 16,
                'withLetters' => true,
                'withNumbers' => true,
                'withSymbols' => true,
                'withSpaces' => false,
                'exception' => null,
            ],
            'letters and numbers and symbols with spaces' => [
                'length' => 16,
                'withLetters' => true,
                'withNumbers' => true,
                'withSymbols' => true,
                'withSpaces' => true,
                'exception' => null,
            ],
            'short password' => [
                'length' => 1,
                'withLetters' => true,
                'withNumbers' => false,
                'withSymbols' => false,
                'withSpaces' => false,
                'exception' => null,
            ],
            'long password' => [
                'length' => 2048,
                'withLetters' => true,
                'withNumbers' => true,
                'withSymbols' => true,
                'withSpaces' => true,
                'exception' => null,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderPasswordCreation
     *
     * @return void
     */
    public function testPasswordCreation(
        int $length,
        bool $withLetters,
        bool $withNumbers,
        bool $withSymbols,
        bool $withSpaces,
        ?string $exception
    ): void {
        if ($exception !== null) {
            $this->expectException($exception);
        }

        $generator = (new Password())
            ->setLength($length)
            ->withLetters($withLetters)
            ->withNumbers($withNumbers)
            ->withSymbols($withSymbols)
            ->withSpaces($withSpaces);

        $password = $generator->build();

        $this->assertTrue(strlen($password) === $length);
        $this->assertChars($generator, 'allowedLetters', $password, $withLetters);
        $this->assertChars($generator, 'allowedNumbers', $password, $withNumbers);
        $this->assertChars($generator, 'allowedSymbols', $password, $withSymbols);

        $hasSpaces = Str::contains($password, ' ');
        $this->assertThat($hasSpaces, $withSpaces ? $this->isTrue() : $this->isFalse());

        $this->assertFalse(Str::substr($password, 0, 1) === ' ');
        $this->assertFalse(Str::substr($password, Str::length($password) - 1, 1) === ' ');
    }

    /**
     * @param  Password  $generator
     * @param  string  $property
     * @param  string  $password
     * @param  bool  $with
     * @return void
     *
     * @throws ReflectionException
     */
    private function assertChars(Password $generator, string $property, string $password, bool $with): void
    {
        $letters = $this->readProperty($generator, $property);
        $hasLetters = preg_match('~['.preg_quote(implode('', $letters), '~').']~iu', $password) === 1;
        $this->assertThat($hasLetters, $with ? $this->isTrue() : $this->isFalse());
    }

    /**
     * @param  Password  $generator
     * @param  string  $property
     * @return string[]
     *
     * @throws ReflectionException
     */
    private function readProperty(Password $generator, string $property): array
    {
        $reflectedClass = new ReflectionClass($generator);
        $reflection = $reflectedClass->getProperty($property);

        return $reflection->getValue($generator);
    }
}
