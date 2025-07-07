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
    public function test_basic()
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

    public function test_single_mimetype()
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

    public function test_multiple_mime_types()
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

    public function test_single_mime()
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

    public function test_multiple_mimes()
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

    public function test_mix_of_mimetypes_and_mimes()
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

    public function test_single_extension()
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

    public function test_multiple_extensions()
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

    public function test_image()
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

    public function test_image_fails_on_svg_by_default()
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

    public function test_size()
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

    public function test_between()
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

    public function test_min()
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

    public function test_min_with_human_readable_size()
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

    public function test_max()
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

    public function test_max_with_human_readable_size()
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

    public function test_max_with_human_readable_size_and_multiple_value()
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

    public function test_macro()
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

    public function test_it_uses_the_correct_validation_message_for_file(): void
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

    public function test_it_can_set_default_using()
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

    public function test_file_size_conversion_with_different_units()
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

    public function test_global_binary_precedence(): void
    {
        $file1010 = UploadedFile::fake()->create('test.txt', 1010);

        $rule = File::default()->binary();

        $this->passes(
            $rule->max(1024),
            $file1010
        );

        $this->fails(
            $rule->max(1000),
            $file1010,
            ['validation.max.file']
        );
    }

    public function test_global_international_precedence(): void
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
            ['validation.size.file']
        );
    }

    public function test_defaults_to_international_when_no_global_setting(): void
    {
        $file1010 = UploadedFile::fake()->create('test.txt', 1010);

        $this->fails(
            File::default()->max('1MB'),
            $file1010,
            ['validation.max.file']
        );
    }

    public function test_numeric_sizes_work_without_units(): void
    {
        $file1000 = UploadedFile::fake()->create('numeric.txt', 1000);

        $this->passes(File::default()->max(1000), $file1000);
        $this->passes(File::default()->binary()->max(1000), $file1000);
        $this->passes(File::default()->international()->max(1000), $file1000);
    }

    public function test_binary_integer_precision_for_large_file_sizes(): void
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

    public function test_international_integer_precision_for_large_file_sizes(): void
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

    public function test_float_precision_for_fractional_sizes(): void
    {
        $file512 = UploadedFile::fake()->create('fractional512.txt', 512);
        $file500 = UploadedFile::fake()->create('fractional500.txt', 500);

        $this->passes(
            File::default()->size('0.5MiB'),
            $file512
        );

        $this->passes(
            File::default()->size('0.5MB'),
            $file500
        );

        $this->fails(
            File::default()->size('0.5MiB'),
            $file500,
            ['validation.size.file']
        );

        $this->fails(
            File::default()->size('0.5MB'),
            $file512,
            ['validation.size.file']
        );
    }

    public function test_binary_vs_international_calculation_accuracy(): void
    {
        $file1010 = UploadedFile::fake()->create('boundary1010.txt', 1010);

        $this->passes(
            File::default()->max('1MiB'),
            $file1010
        );

        $this->fails(
            File::default()->max('1MB'),
            $file1010,
            ['validation.max.file']
        );

        $file900 = UploadedFile::fake()->create('small900.txt', 900);

        $this->passes(
            File::default()->max('1MiB'),
            $file900
        );

        $this->passes(
            File::default()->max('1MB'),
            $file900
        );
    }

    public function test_binary_large_file_size_precision(): void
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

    public function test_international_large_file_size_precision(): void
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

    public function test_overflow_protection_for_large_integer_values(): void
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

    public function test_overflow_protection_with_fractional_values(): void
    {
        $file1536 = UploadedFile::fake()->create('fractional.txt', 1536);

        $this->passes(
            File::default()->size('1.5MiB'),
            $file1536
        );

        $file1500 = UploadedFile::fake()->create('fractional.txt', 1500);

        $this->passes(
            File::default()->size('1.5MB'),
            $file1500
        );
    }

    public function test_case_insensitive_suffixes(): void
    {
        $file1024 = UploadedFile::fake()->create('case.txt', 1024);

        $this->passes(File::default()->size('1MiB'), $file1024);
        $this->passes(File::default()->size('1mib'), $file1024);
        $this->passes(File::default()->size('1Mib'), $file1024);
        $this->passes(File::default()->size('1MIB'), $file1024);
    }

    public function test_invalid_size_suffix_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file size suffix.');

        File::default()->max('5xyz');
    }

    public function test_zero_and_very_small_file_sizes(): void
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

    public function test_whitespace_handling_in_file_sizes(): void
    {
        $file2048 = UploadedFile::fake()->create('whitespace.txt', 2048);
        $file2000 = UploadedFile::fake()->create('whitespace.txt', 2000);

        $this->passes(
            File::default()->size(' 2MiB '),
            $file2048
        );

        $this->passes(
            File::default()->size(' 2MB '),
            $file2000
        );

        $this->passes(
            File::default()->size('2 MiB'),
            $file2048
        );
    }

    public function test_comma_separated_number_parsing(): void
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

    public function test_negative_file_size_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid numeric value in file size.');

        File::default()->max('-5MB');
    }

    public function test_binary_fluent_chaining(): void
    {
        $file1024 = UploadedFile::fake()->create('chain.txt', 1024);

        $rule = File::default()->binary()
            ->types(['txt'])
            ->min('1MB')
            ->max('2MB');

        $this->passes($rule, $file1024);
    }

    public function test_international_fluent_chaining(): void
    {
        $file1000 = UploadedFile::fake()->create('chain.txt', 1000);

        $rule = File::default()->international()
            ->types(['txt'])
            ->min('1MB')
            ->max('2MB');

        $this->passes($rule, $file1000);
    }

    public function test_units_methods_return_new_instances(): void
    {
        $binary1 = File::default()->binary();
        $binary2 = File::default()->binary();
        $international1 = File::default()->international();

        $this->assertNotSame($binary1, $binary2);
        $this->assertNotSame($binary1, $international1);
    }

    public function test_units_backwards_compatibility(): void
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

    public function test_binary_fluent_method(): void
    {
        $file1024 = UploadedFile::fake()->create('binary.txt', 1024);

        $rule = File::default()
            ->types(['txt'])
            ->binary()
            ->size('1MiB');

        $this->passes($rule, $file1024);
    }

    public function test_international_fluent_method(): void
    {
        $file1000 = UploadedFile::fake()->create('international.txt', 1000);

        $rule = File::default()
            ->types(['txt'])
            ->international()
            ->size('1MB');

        $this->passes($rule, $file1000);
    }

    public function test_units_method_chaining(): void
    {
        $file1024 = UploadedFile::fake()->create('chaining.txt', 1024);

        $rule = File::default()
            ->types(['txt'])
            ->international()
            ->binary()
            ->size('1MiB');

        $this->passes($rule, $file1024);
    }

    public function test_instance_methods_return_same_object(): void
    {
        $originalRule = File::default()->types(['txt']);

        $binaryRule = $originalRule->binary();
        $internationalRule = $originalRule->international();

        $this->assertSame($originalRule, $binaryRule);
        $this->assertSame($originalRule, $internationalRule);

        $newBinary = File::default()->binary();
        $newInternational = File::default()->international();

        $this->assertNotSame($originalRule, $newBinary);
        $this->assertNotSame($originalRule, $newInternational);
    }

    public function test_suffix_precedence_over_instance_methods(): void
    {
        $file1000 = UploadedFile::fake()->create('test.txt', 1000);
        $file1030 = UploadedFile::fake()->create('test.txt', 1030);

        $this->passes(
            File::default()->binary()->max('1MB'),
            $file1000
        );

        $this->fails(
            File::default()->international()->max('1MiB'),
            $file1030,
            ['validation.max.file']
        );
    }

    public function test_naked_values_fallback_to_instance_methods(): void
    {
        $file1000 = UploadedFile::fake()->create('numeric.txt', 1000);

        $this->passes(
            File::default()->binary()->max(1024),
            $file1000
        );

        $this->passes(
            File::default()->international()->max(1000),
            $file1000
        );

        $this->fails(
            File::default()->international()->max(999),
            $file1000,
            ['validation.max.file']
        );
    }

    public function test_comprehensive_binary_suffixes(): void
    {
        $file1 = UploadedFile::fake()->create('1kb.txt', 1);
        $file1024 = UploadedFile::fake()->create('1mb.txt', 1024);
        $file1048576 = UploadedFile::fake()->create('1gb.txt', 1048576);

        $this->passes(File::default()->size('1KiB'), $file1);
        $this->passes(File::default()->size('1MiB'), $file1024);
        $this->passes(File::default()->size('1GiB'), $file1048576);
    }

    public function test_mixed_unit_constraints(): void
    {
        $file1500 = UploadedFile::fake()->create('mixed.txt', 1500);

        $rule = File::default()
            ->min('1MB')
            ->max('2MiB');

        $this->passes($rule, $file1500);

        $file500 = UploadedFile::fake()->create('small.txt', 500);

        $this->fails(
            $rule,
            $file500,
            ['validation.between.file']
        );
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
