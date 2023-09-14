<?php

namespace Illuminate\Tests\Validation\Concerns;

use Illuminate\Validation\Concerns\ValidatesAttributes;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ValidatesAttributesTest extends TestCase
{
    use ValidatesAttributes;

    /**
     * @dataProvider validJsonDataProvider
     */
    public function testValidateJsonWithValidJson(mixed $validJson): void
    {
        $isJsonValid = $this->validateJson('json_content', $validJson);

        $this->assertTrue($isJsonValid);
    }

    /**
     * @dataProvider invalidJsonDataProvider
     */
    public function testValidateJsonWithInvalidJson(mixed $invalidJson): void
    {
        $isJsonValid = $this->validateJson('json_content', $invalidJson);

        $this->assertFalse($isJsonValid);
    }

    /**
     * @return \Generator
     */
    public static function validJsonDataProvider(): \Generator
    {
        $jsonClass = new class {
            public function __toString()
            {
                return '{"message": "Olá!"}';
            }
        };

        $mockedUploadedFile = self::createFakeUploadedFile('{"message": "Hola"}', 'json');

        yield 'json string' => ['{"message": "Hi!"}'];
        yield 'class with json __toString' => [$jsonClass];
        yield 'uploaded json file' => [$mockedUploadedFile];
    }

    /**
     * @return \Generator
     */
    public static function invalidJsonDataProvider(): \Generator
    {
        $invalidClass = new class {
            public function sayHi()
            {
                return 'Hi!';
            }
        };

        $invalidContentFile = self::createFakeUploadedFile('{"message":,,}', 'json');
        $invalidExtensionFile = self::createFakeUploadedFile('{"message":"Hello"}', 'pdf');

        yield 'array' => [['message' => 'konnichiwa']];
        yield 'class without json __toString' => [$invalidClass];
        yield 'invalid json string' => ['{"message":,,,}'];
        yield 'invalid uploaded file json' => [$invalidContentFile];
        yield 'invalid uploaded file extension' => [$invalidExtensionFile];
    }

    private static function createFakeUploadedFile(string $fileContent, string $fileExtension): UploadedFile
    {
        /** @var UploadedFile&MockInterface */
        $mockedUploadedFile = mock(UploadedFile::class);

        $mockedUploadedFile
            ->shouldReceive('getContent')
            ->once()
            ->andReturn($fileContent);

        $mockedUploadedFile
            ->shouldReceive('getExtension')
            ->once()
            ->andReturn($fileExtension);

        return $mockedUploadedFile;
    }
}
