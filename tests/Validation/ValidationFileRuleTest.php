<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidationFileRuleTest extends TestCase
{
    public function testBasic()
    {
        $this->fails(
            File::default(),
            'foo',
            ['validation.file'],
        );

        $this->passes(
            File::default(),
            UploadedFile::fake()->create('foo.bar'),
        );

        $this->passes(File::default(), null);
    }

    protected function fails($rule, $values, $messages)
    {
        $this->assertValidationRules($rule, $values, false, $messages);
    }

    protected function assertValidationRules($rule, $values, $result, $messages)
    {
        $values = Arr::wrap($values);

        foreach ($values as $value) {
            $v = new Validator(
                resolve('translator'),
                ['my_file' => $value],
                ['my_file' => is_object($rule) ? clone $rule : $rule]
            );

            $this->assertSame($result, $v->passes());

            $this->assertSame(
                $result ? [] : ['my_file' => $messages],
                $v->messages()->toArray()
            );
        }
    }

    protected function passes($rule, $values)
    {
        $this->assertValidationRules($rule, $values, true, []);
    }

    public function testSingleMimetype()
    {
        $this->fails(
            File::types('text/plain'),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png')),
            ['validation.mimetypes']
        );

        $this->passes(
            File::types('image/png'),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png')),
        );
    }

    public function testMultipleMimeTypes()
    {
        $this->fails(
            File::types(['text/plain', 'image/jpeg']),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png')),
            ['validation.mimetypes']
        );

        $this->passes(
            File::types(['text/plain', 'image/png']),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png')),
        );
    }

    public function testSingleMime()
    {
        $this->fails(
            File::types('txt'),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png')),
            ['validation.mimes']
        );

        $this->passes(
            File::types('png'),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png')),
        );
    }

    public function testMultipleMimes()
    {
        $this->fails(
            File::types(['png', 'jpg', 'jpeg', 'svg']),
            UploadedFile::fake()->createWithContent('foo.txt', 'Hello World!'),
            ['validation.mimes']
        );

        $this->passes(
            File::types(['png', 'jpg', 'jpeg', 'svg']),
            [
                UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png')),
                UploadedFile::fake()->createWithContent('foo.svg', file_get_contents(__DIR__.'/fixtures/image.svg')),
            ]
        );
    }

    public function testMixOfMimetypesAndMimes()
    {
        $this->fails(
            File::types(['png', 'image/png']),
            UploadedFile::fake()->createWithContent('foo.txt', 'Hello World!'),
            ['validation.mimetypes', 'validation.mimes']
        );

        $this->passes(
            File::types(['png', 'image/png']),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png')),
        );
    }

    public function testSingleExtension()
    {
        $this->fails(
            File::default()->extensions('png'),
            UploadedFile::fake()->createWithContent('foo', file_get_contents(__DIR__.'/fixtures/image.png')),
            ['validation.extensions']
        );

        $this->fails(
            File::default()->extensions('png'),
            UploadedFile::fake()->createWithContent('foo.jpg', file_get_contents(__DIR__.'/fixtures/image.png')),
            ['validation.extensions']
        );

        $this->fails(
            File::default()->extensions('jpeg'),
            UploadedFile::fake()->createWithContent('foo.jpg', file_get_contents(__DIR__.'/fixtures/image.png')),
            ['validation.extensions']
        );

        $this->passes(
            File::default()->extensions('png'),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png')),
        );
    }

    public function testMultipleExtensions()
    {
        $this->fails(
            File::default()->extensions(['png', 'jpeg', 'jpg']),
            UploadedFile::fake()->createWithContent('foo', file_get_contents(__DIR__.'/fixtures/image.png')),
            ['validation.extensions']
        );

        $this->fails(
            File::default()->extensions(['png', 'jpeg']),
            UploadedFile::fake()->createWithContent('foo.jpg', file_get_contents(__DIR__.'/fixtures/image.png')),
            ['validation.extensions']
        );

        $this->passes(
            File::default()->extensions(['png', 'jpeg', 'jpg']),
            UploadedFile::fake()->createWithContent('foo.png', file_get_contents(__DIR__.'/fixtures/image.png')),
        );
    }

    public function testImage()
    {
        $this->fails(
            File::image(),
            UploadedFile::fake()->createWithContent('foo.txt', 'Hello World!'),
            ['validation.image']
        );

        $this->passes(
            File::image(),
            UploadedFile::fake()->image('foo.png'),
        );
    }

    public function testImageFailsOnSvgByDefault()
    {
        $maliciousSvgFileWithXSS = UploadedFile::fake()->createWithContent(
            name: 'foo.svg',
            content: <<<'XML'
                    <svg xmlns="http://www.w3.org/2000/svg" width="383" height="97" viewBox="0 0 383 97">
                        <text x="10" y="50" font-size="30" fill="black">XSS Logo</text>
                        <script>alert('XSS');</script>
                    </svg>
                    XML
        );

        $this->fails(
            File::image(),
            $maliciousSvgFileWithXSS,
            ['validation.image']
        );
        $this->fails(
            Rule::imageFile(),
            $maliciousSvgFileWithXSS,
            ['validation.image']
        );

        $this->passes(
            File::image(allowSvg: true),
            $maliciousSvgFileWithXSS
        );
        $this->passes(
            Rule::imageFile(allowSvg: true),
            $maliciousSvgFileWithXSS
        );
    }

    public function testSize()
    {
        $this->fails(
            File::default()->size(1024),
            [
                UploadedFile::fake()->create('foo.txt', 1025),
                UploadedFile::fake()->create('foo.txt', 1023),
            ],
            ['validation.size.file']
        );

        $this->passes(
            File::default()->size(1024),
            UploadedFile::fake()->create('foo.txt', 1024),
        );
    }

    public function testSizeWithBinaryUnits(): void
    {
        $this->passes(
            File::default()->size('1MB', File::BINARY),
            UploadedFile::fake()->create('foo.txt', 1024)
        );

        $this->fails(
            File::default()->size('1MB', File::BINARY),
            [
                UploadedFile::fake()->create('foo.txt', 1023),
                UploadedFile::fake()->create('foo.txt', 1025),
            ],
            ['validation.size.file']
        );
    }

    public function testSizeWithInternationalUnits(): void
    {
        $this->passes(
            File::default()->size('1MB', File::INTERNATIONAL),
            UploadedFile::fake()->create('foo.txt', 1000)
        );

        $this->fails(
            File::default()->size('1MB', File::INTERNATIONAL),
            [
                UploadedFile::fake()->create('foo.txt', 999),
                UploadedFile::fake()->create('foo.txt', 1001),
            ],
            ['validation.size.file']
        );
    }

    public function testBetween()
    {
        $this->fails(
            File::default()->between(1024, 2048),
            [
                UploadedFile::fake()->create('foo.txt', 1023),
                UploadedFile::fake()->create('foo.txt', 2049),
            ],
            ['validation.between.file']
        );

        $this->passes(
            File::default()->between(1024, 2048),
            [
                UploadedFile::fake()->create('foo.txt', 1024),
                UploadedFile::fake()->create('foo.txt', 2048),
                UploadedFile::fake()->create('foo.txt', 1025),
                UploadedFile::fake()->create('foo.txt', 2047),
            ]
        );
    }

    public function testBetweenWithBinaryUnits(): void
    {
        $this->passes(
            File::default()->between('1MB', '2MB', File::BINARY),
            [
                UploadedFile::fake()->create('foo.txt', 1024),
                UploadedFile::fake()->create('foo.txt', 1500),
                UploadedFile::fake()->create('foo.txt', 2048),
            ]
        );

        $this->fails(
            File::default()->between('1MB', '2MB', File::BINARY),
            [
                UploadedFile::fake()->create('foo.txt', 1023),
                UploadedFile::fake()->create('foo.txt', 2049),
            ],
            ['validation.between.file']
        );
    }

    public function testBetweenWithInternationalUnits(): void
    {
        $this->passes(
            File::default()->between('1MB', '2MB', File::INTERNATIONAL),
            [
                UploadedFile::fake()->create('foo.txt', 1000),
                UploadedFile::fake()->create('foo.txt', 1500),
                UploadedFile::fake()->create('foo.txt', 2000),
            ]
        );

        $this->fails(
            File::default()->between('1MB', '2MB', File::INTERNATIONAL),
            [
                UploadedFile::fake()->create('foo.txt', 999),
                UploadedFile::fake()->create('foo.txt', 2001),
            ],
            ['validation.between.file']
        );
    }

    public function testMin()
    {
        $this->fails(
            File::default()->min(1024),
            UploadedFile::fake()->create('foo.txt', 1023),
            ['validation.min.file']
        );

        $this->passes(
            File::default()->min(1024),
            [
                UploadedFile::fake()->create('foo.txt', 1024),
                UploadedFile::fake()->create('foo.txt', 1025),
                UploadedFile::fake()->create('foo.txt', 2048),
            ]
        );
    }

    public function testMinWithHumanReadableSize()
    {
        $this->fails(
            File::default()->min('1024kb'),
            UploadedFile::fake()->create('foo.txt', 1023),
            ['validation.min.file']
        );

        $this->passes(
            File::default()->min('1024kb'),
            [
                UploadedFile::fake()->create('foo.txt', 1024),
                UploadedFile::fake()->create('foo.txt', 1025),
                UploadedFile::fake()->create('foo.txt', 2048),
            ]
        );
    }

    public function testMinWithBinaryUnits(): void
    {
        $this->passes(
            File::default()->min('1MB', File::BINARY),
            [
                UploadedFile::fake()->create('foo.txt', 1024),
                UploadedFile::fake()->create('foo.txt', 1025),
                UploadedFile::fake()->create('foo.txt', 2048),
            ]
        );

        $this->fails(
            File::default()->min('1MB', File::BINARY),
            UploadedFile::fake()->create('foo.txt', 1023),
            ['validation.min.file']
        );
    }

    public function testMinWithInternationalUnits(): void
    {
        $this->passes(
            File::default()->min('1MB', File::INTERNATIONAL),
            [
                UploadedFile::fake()->create('foo.txt', 1000),
                UploadedFile::fake()->create('foo.txt', 1001),
                UploadedFile::fake()->create('foo.txt', 2000),
            ]
        );

        $this->fails(
            File::default()->min('1MB', File::INTERNATIONAL),
            UploadedFile::fake()->create('foo.txt', 999),
            ['validation.min.file']
        );
    }

    public function testMax()
    {
        $this->fails(
            File::default()->max(1024),
            UploadedFile::fake()->create('foo.txt', 1025),
            ['validation.max.file']
        );

        $this->passes(
            File::default()->max(1024),
            [
                UploadedFile::fake()->create('foo.txt', 1024),
                UploadedFile::fake()->create('foo.txt', 1023),
                UploadedFile::fake()->create('foo.txt', 512),
            ]
        );
    }

    public function testMaxWithHumanReadableSize()
    {
        $this->fails(
            File::default()->max('1024kb'),
            UploadedFile::fake()->create('foo.txt', 1025),
            ['validation.max.file']
        );

        $this->passes(
            File::default()->max('1024kb'),
            [
                UploadedFile::fake()->create('foo.txt', 1024),
                UploadedFile::fake()->create('foo.txt', 1023),
                UploadedFile::fake()->create('foo.txt', 512),
            ]
        );
    }

    public function testMaxWithHumanReadableSizeAndMultipleValue()
    {
        $this->fails(
            File::default()->max('1mb'),
            UploadedFile::fake()->create('foo.txt', 1025),
            ['validation.max.file']
        );

        $this->passes(
            File::default()->max('1mb'),
            [
                UploadedFile::fake()->create('foo.txt', 1000),
                UploadedFile::fake()->create('foo.txt', 999),
                UploadedFile::fake()->create('foo.txt', 512),
            ]
        );
    }

    public function testMaxWithBinaryUnits(): void
    {
        $this->passes(
            File::default()->max('1MB', File::BINARY),
            [
                UploadedFile::fake()->create('foo.txt', 1024),
                UploadedFile::fake()->create('foo.txt', 1023),
                UploadedFile::fake()->create('foo.txt', 512),
            ]
        );

        $this->fails(
            File::default()->max('1MB', File::BINARY),
            UploadedFile::fake()->create('foo.txt', 1025),
            ['validation.max.file']
        );
    }

    public function testMaxWithInternationalUnits(): void
    {
        $this->passes(
            File::default()->max('1MB', File::INTERNATIONAL),
            [
                UploadedFile::fake()->create('foo.txt', 1000),
                UploadedFile::fake()->create('foo.txt', 999),
                UploadedFile::fake()->create('foo.txt', 500),
            ]
        );

        $this->fails(
            File::default()->max('1MB', File::INTERNATIONAL),
            UploadedFile::fake()->create('foo.txt', 1001),
            ['validation.max.file']
        );
    }

    public function testMacro()
    {
        File::macro('toDocument', function () {
            return static::default()->rules('mimes:txt,csv');
        });

        $this->fails(
            File::toDocument(),
            UploadedFile::fake()->create('foo.png'),
            ['validation.mimes']
        );

        $this->passes(
            File::toDocument(),
            [
                UploadedFile::fake()->create('foo.txt'),
                UploadedFile::fake()->create('foo.csv'),
            ]
        );
    }

    public function testItUsesTheCorrectValidationMessageForFile(): void
    {
        file_put_contents($path = __DIR__.'/test.json', 'this-is-a-test');

        $file = new \Illuminate\Http\File($path);

        $this->fails(
            ['max:0'],
            $file,
            ['validation.max.file']
        );

        unlink($path);
    }

    public function testItCanSetDefaultUsing()
    {
        $this->assertInstanceOf(File::class, File::default());

        File::defaults(function () {
            return File::types('txt')->max(12 * 1024);
        });

        $this->fails(
            File::default(),
            UploadedFile::fake()->create('foo.png', 13 * 1024),
            [
                'validation.mimes',
                'validation.max.file',
            ]
        );

        File::defaults(File::image()->between(1024, 2048));

        $this->passes(
            File::default(),
            UploadedFile::fake()->create('foo.png', 1.5 * 1024),
        );
    }

    public function testFileSizeConversionWithDifferentUnits()
    {
        $this->passes(
            File::image()->size('5MB'),
            UploadedFile::fake()->create('foo.png', 5000)
        );

        $this->passes(
            File::image()->size(' 2gb '),
            UploadedFile::fake()->create('foo.png', 2 * 1000000)
        );

        $this->passes(
            File::image()->size('1Tb'),
            UploadedFile::fake()->create('foo.png', 1000000000)
        );

        $this->expectException(\InvalidArgumentException::class);
        File::image()->size('10xyz');
    }

    public function testFileConstants(): void
    {
        $this->assertEquals('binary', File::BINARY);
        $this->assertEquals('international', File::INTERNATIONAL);
    }


    public function testGlobalBinaryPrecedence(): void
    {
        $file1010 = UploadedFile::fake()->create('test.txt', 1010);

        $rule = File::default()->binary();

        $this->passes(
            $rule->max('1MB'),
            $file1010
        );

        $this->fails(
            $rule->min('1MB'),
            $file1010,
            ['validation.size.file'] // when min=max, validation.size.file instead of validation.max.file
        );
    }

    public function testGlobalInternationalPrecedence(): void
    {
        $file1010 = UploadedFile::fake()->create('test.txt', 1010);

        $rule = File::default()->international();

        $this->passes(
            $rule->min('1MB'),
            $file1010
        );

        $this->fails(
            $rule->max('1MB'),
            $file1010,
            ['validation.size.file'] // when min=max, validation.size.file instead of validation.max.file
        );
    }

    public function testExplicitParameterOverridesGlobalSetting(): void
    {
        $file1010 = UploadedFile::fake()->create('test.txt', 1010);

        $this->fails(
            File::default()->binary()->max('1MB', File::INTERNATIONAL),
            $file1010,
            ['validation.max.file']
        );

        $this->passes(
            File::default()->international()->max('1MB', File::BINARY),
            $file1010
        );
    }

    public function testDefaultsToInternationalWhenNoGlobalSetting(): void
    {
        $file1010 = UploadedFile::fake()->create('test.txt', 1010);

        $this->fails(
            File::default()->max('1MB'),
            $file1010,
            ['validation.max.file']
        );
    }

    public function testBinaryVsInternationalDifference(): void
    {
        $file1010 = UploadedFile::fake()->create('boundary.txt', 1010);

        $this->passes(
            File::default()->max('1MB', File::BINARY),
            $file1010
        );

        $this->fails(
            File::default()->max('1MB', File::INTERNATIONAL),
            $file1010,
            ['validation.max.file']);
    }

    public function testNumericSizesWorkWithoutUnits(): void
    {
        $file1000 = UploadedFile::fake()->create('numeric.txt', 1000);

        $this->passes(File::default()->max(1000), $file1000);
        $this->passes(File::default()->binary()->max(1000), $file1000);
        $this->passes(File::default()->international()->max(1000), $file1000);
    }

    public function testDecimalSizes(): void
    {
        $file512 = UploadedFile::fake()->create('half.txt', 512);
        $file500 = UploadedFile::fake()->create('half.txt', 500);

        $this->passes(
            File::default()->size('0.5MB', File::BINARY),
            $file512
        );

        $this->fails(File::default()->size('0.5MB', File::BINARY),
            $file500,
            ['validation.size.file']
        );

        $this->passes(
            File::default()->size('0.5MB', File::INTERNATIONAL),
            $file500
        );

        $this->fails(
            File::default()->size('0.5MB', File::INTERNATIONAL),
            $file512,
            ['validation.size.file']
        );
    }

    public function testLargerUnits(): void
    {
        $file1048576 = UploadedFile::fake()->create('big.txt', 1048576);
        $file1000000 = UploadedFile::fake()->create('big.txt', 1000000);

        $this->passes(
            File::default()->size('1GB', File::BINARY),
            $file1048576
        );

        $this->passes(
            File::default()->size('1GB', File::INTERNATIONAL),
            $file1000000
        );
    }

    public function testBinaryIntegerPrecisionForLargeFileSizes(): void
    {
        $file999999 = UploadedFile::fake()->create('large999999.txt', 999999);
        
        $this->passes(
            File::default()->binary()->max('1000000KB'),
            $file999999
        );
        
        $this->fails(
            File::default()->binary()->max('999998KB'),
            $file999999,
            ['validation.max.file']
        );
    }

    public function testInternationalIntegerPrecisionForLargeFileSizes(): void
    {
        $file999999 = UploadedFile::fake()->create('large999999.txt', 999999);
        
        $this->passes(
            File::default()->international()->max('1000000KB'),
            $file999999
        );
        
        $this->fails(
            File::default()->international()->max('999998KB'),
            $file999999,
            ['validation.max.file']
        );
    }

    public function testFloatPrecisionForFractionalSizes(): void
    {
        $file512 = UploadedFile::fake()->create('fractional512.txt', 512);
        $file500 = UploadedFile::fake()->create('fractional500.txt', 500);
        
        $this->passes(
            File::default()->binary()->size('0.5MB'),
            $file512
        );
        
        $this->passes(
            File::default()->international()->size('0.5MB'),
            $file500
        );
        
        $this->fails(
            File::default()->binary()->size('0.5MB'),
            $file500,
            ['validation.size.file']
        );
        
        $this->fails(
            File::default()->international()->size('0.5MB'),
            $file512,
            ['validation.size.file']
        );
    }

    public function testBinaryVsInternationalCalculationAccuracy(): void
    {
        $file1010 = UploadedFile::fake()->create('boundary1010.txt', 1010);
        
        $this->passes(
            File::default()->binary()->max('1MB'),
            $file1010
        );
        
        $this->fails(
            File::default()->international()->max('1MB'),
            $file1010,
            ['validation.max.file']
        );
        
        $file900 = UploadedFile::fake()->create('small900.txt', 900);
        
        $this->passes(
            File::default()->binary()->max('1MB'),
            $file900
        );
        
        $this->passes(
            File::default()->international()->max('1MB'),
            $file900
        );
    }

    public function testBinaryLargeFileSizePrecision(): void
    {
        $file500000 = UploadedFile::fake()->create('huge500000.txt', 500000);
        
        $this->passes(
            File::default()->binary()->between('400MB', '600MB'),
            $file500000
        );
        
        $this->passes(
            File::default()->binary()->max('1GB'),
            $file500000
        );
        
        $this->fails(
            File::default()->binary()->max('488MB'),
            $file500000,
            ['validation.max.file']
        );
    }

    public function testInternationalLargeFileSizePrecision(): void
    {
        $file500000 = UploadedFile::fake()->create('huge500000.txt', 500000);
        
        $this->passes(
            File::default()->international()->between('400MB', '600MB'),
            $file500000
        );
        
        $this->passes(
            File::default()->international()->max('1GB'),
            $file500000
        );
        
        $this->fails(
            File::default()->international()->max('499MB'),
            $file500000,
            ['validation.max.file']
        );
    }

    public function testOverflowProtectionForLargeIntegerValues(): void
    {
        $fileLarge = UploadedFile::fake()->create('overflow.txt', 2000000000);
        
        $this->passes(
            File::default()->binary()->max('2000000MB'),
            $fileLarge
        );
        
        $this->passes(
            File::default()->international()->max('2000000MB'),
            $fileLarge
        );
    }

    public function testOverflowProtectionWithFractionalValues(): void
    {
        $file1536 = UploadedFile::fake()->create('fractional.txt', 1536);
        
        $this->passes(
            File::default()->binary()->size('1.5MB'),
            $file1536
        );
        
        $file1500 = UploadedFile::fake()->create('fractional.txt', 1500);
        
        $this->passes(
            File::default()->international()->size('1.5MB'),
            $file1500
        );
    }

    public function testCaseInsensitiveSuffixes(): void
    {
        $file1024 = UploadedFile::fake()->create('case.txt', 1024);

        $this->passes(File::default()->binary()->size('1MB'), $file1024);
        $this->passes(File::default()->binary()->size('1mb'), $file1024);
        $this->passes(File::default()->binary()->size('1Mb'), $file1024);
        $this->passes(File::default()->binary()->size('1mB'), $file1024);
    }

    public function testInvalidSizeSuffixThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file size suffix.');

        File::default()->max('5xyz');
    }

    public function testZeroAndVerySmallFileSizes(): void
    {
        $fileZero = UploadedFile::fake()->create('zero.txt', 0);
        $fileOne = UploadedFile::fake()->create('tiny.txt', 1);
        
        $this->passes(
            File::default()->min('0KB'),
            $fileZero
        );
        
        $this->passes(
            File::default()->size('1KB'),
            $fileOne
        );
        
        $this->passes(
            File::default()->binary()->max('0.001MB'),
            $fileOne
        );
    }

    public function testWhitespaceHandlingInFileSizes(): void
    {
        $file2048 = UploadedFile::fake()->create('whitespace.txt', 2048);
        $file2000 = UploadedFile::fake()->create('whitespace.txt', 2000);
        
        $this->passes(
            File::default()->binary()->size(' 2MB '),
            $file2048
        );
        
        $this->passes(
            File::default()->international()->size(' 2MB '),
            $file2000
        );
        
        $this->passes(
            File::default()->binary()->size('2 MB'),
            $file2048
        );
    }

    public function testCommaSeparatedNumberParsing(): void
    {
        $file1024 = UploadedFile::fake()->create('comma.txt', 1024);
        $file10240 = UploadedFile::fake()->create('large.txt', 10240);
        
        $this->passes(
            File::default()->binary()->size('1,024KB'),
            $file1024
        );
        
        $this->passes(
            File::default()->binary()->size('10,240KB'),
            $file10240
        );
        
        $this->passes(
            File::default()->international()->size('1,024KB'),
            $file1024
        );
    }

    public function testNegativeFileSizeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid numeric value in file size.');

        File::default()->max('-5MB');
    }

    public function testBinaryFluentChaining(): void
    {
        $file1024 = UploadedFile::fake()->create('chain.txt', 1024);

        $rule = File::default()->binary()
            ->types(['txt'])
            ->min('1MB')
            ->max('2MB');

        $this->passes($rule, $file1024);
    }

    public function testInternationalFluentChaining(): void
    {
        $file1000 = UploadedFile::fake()->create('chain.txt', 1000);

        $rule = File::default()->international()
            ->types(['txt'])
            ->min('1MB')
            ->max('2MB');

        $this->passes($rule, $file1000);
    }

    public function testMixedUnitsInSameRule(): void
    {
        $file1500 = UploadedFile::fake()->create('mixed.txt', 1500);

        $rule = File::default()->binary()
            ->min('1MB')
            ->max('2MB', File::INTERNATIONAL);

        $this->passes($rule, $file1500);
    }

    public function testComplexUnitsScenarioWithMultipleConstraints(): void
    {
        $file1500 = UploadedFile::fake()->create('complex.txt', 1500);

        $rule = File::default()->binary()
            ->types(['txt'])
            ->min('1MB', File::INTERNATIONAL)
            ->max('1.4MB', File::BINARY);

        $this->fails($rule, $file1500, ['validation.between.file']);
    }

    public function testUnitsMethodsReturnNewInstances(): void
    {
        $binary1 = File::default()->binary();
        $binary2 = File::default()->binary();
        $international1 = File::default()->international();

        $this->assertNotSame($binary1, $binary2);
        $this->assertNotSame($binary1, $international1);
    }

    public function testUnitsBackwardsCompatibility(): void
    {
        $file1000 = UploadedFile::fake()->create('compat.txt', 1000);

        $this->passes(
            File::types(['txt'])->max('1MB'),
            $file1000
        );

        $this->passes(
            File::default()->between(500, 1500),
            $file1000
        );

        $this->passes(
            File::default()->size(1000),
            $file1000
        );
    }

    public function testBinaryFluentMethod(): void
    {
        $file1024 = UploadedFile::fake()->create('binary.txt', 1024);

        // Create instance then call binary() method
        $rule = File::default()
            ->types(['txt'])
            ->binary()
            ->size('1MB');

        $this->passes($rule, $file1024);
    }

    public function testInternationalFluentMethod(): void
    {
        $file1000 = UploadedFile::fake()->create('international.txt', 1000);

        // Create instance then call international() method
        $rule = File::default()
            ->types(['txt'])
            ->international()
            ->size('1MB');

        $this->passes($rule, $file1000);
    }

    public function testUnitsMethodChaining(): void
    {
        $file1024 = UploadedFile::fake()->create('chaining.txt', 1024);

        // Test switching between binary and international on same instance
        $rule = File::default()
            ->types(['txt'])
            ->international()  // Start with international
            ->binary()         // Switch to binary
            ->size('1MB');        // 1MB binary = 1024KB

        $this->passes($rule, $file1024);
    }

    public function testInstanceMethodsReturnSameObject(): void
    {
        $originalRule = File::default()->types(['txt']);
        
        // Instance methods should return the same object
        $binaryRule = $originalRule->binary();
        $internationalRule = $originalRule->international();
        
        $this->assertSame($originalRule, $binaryRule);
        $this->assertSame($originalRule, $internationalRule);
        
        // Creating new instances should return different objects
        $newBinary = File::default()->binary();
        $newInternational = File::default()->international();
        
        $this->assertNotSame($originalRule, $newBinary);
        $this->assertNotSame($originalRule, $newInternational);
    }

    protected function setUp(): void
    {
        $container = Container::getInstance();

        $container->bind('translator', function () {
            return new Translator(
                new ArrayLoader, 'en'
            );
        });

        Facade::setFacadeApplication($container);

        (new ValidationServiceProvider($container))->register();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        Facade::clearResolvedInstances();

        Facade::setFacadeApplication(null);

        File::$defaultCallback = null;
    }
}
