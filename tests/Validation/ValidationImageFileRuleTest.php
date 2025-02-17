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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ValidationImageFileRuleTest extends TestCase
{

    #[Test]
    public function dimensions()
    {
        $this->fails(
            File::image()->dimensions(Rule::dimensions()->width(100)->height(100)),
            UploadedFile::fake()->image('foo.png', 101, 101),
            ['validation.width', 'validation.height'],
        );

        $this->passes(
            File::image()->dimensions(Rule::dimensions()->width(100)->height(100)),
            UploadedFile::fake()->image('foo.png', 100, 100),
        );
    }

    #[Test]
    public function dimensions_with_custom_image_size_method()
    {
        $this->fails(
            File::image()->dimensions(Rule::dimensions()->width(100)->height(100)),
            new UploadedFileWithCustomImageSizeMethod(stream_get_meta_data($tmpFile = tmpfile())['uri'], 'foo.png'),
            ['validation.width', 'validation.height'],
        );

        $this->passes(
            File::image()->dimensions(Rule::dimensions()->width(200)->height(200)),
            new UploadedFileWithCustomImageSizeMethod(stream_get_meta_data($tmpFile = tmpfile())['uri'], 'foo.png'),
        );
    }

    #[Test]
    public function dimension_with_the_ratio_method()
    {
        $this->fails(
            File::image()->dimensions(Rule::dimensions()->ratio(1)),
            UploadedFile::fake()->image('foo.png', 105, 100),
            ['validation.ratio'],
        );

        $this->passes(
            File::image()->dimensions(Rule::dimensions()->ratio(1)),
            UploadedFile::fake()->image('foo.png', 100, 100),
        );
    }

    #[Test]
    public function dimension_with_the_min_ratio_method()
    {
        $this->fails(
            File::image()->dimensions(Rule::dimensions()->minRatio(1 / 2)),
            UploadedFile::fake()->image('foo.png', 100, 100),
            ['validation.min_ratio'],
        );

        $this->passes(
            File::image()->dimensions(Rule::dimensions()->minRatio(1 / 2)),
            UploadedFile::fake()->image('foo.png', 100, 200),
        );
    }

    #[Test]
    public function dimension_with_the_max_ratio_method()
    {
        $this->fails(
            File::image()->dimensions(Rule::dimensions()->maxRatio(1 / 2)),
            UploadedFile::fake()->image('foo.png', 100, 300),
            ['validation.max_ratio'],
        );

        $this->passes(
            File::image()->dimensions(Rule::dimensions()->maxRatio(1 / 2)),
            UploadedFile::fake()->image('foo.png', 100, 100),
        );
    }

    #[Test]
    public function dimension_with_the_ratio_between_method()
    {
        $this->fails(
            File::image()->dimensions(Rule::dimensions()->ratioBetween(1 / 2, 1 / 3)),
            UploadedFile::fake()->image('foo.png', 100, 100),
            ['validation.ratio_between'],
        );

        $this->passes(
            File::image()->dimensions(Rule::dimensions()->ratioBetween(1 / 2, 1 / 3)),
            UploadedFile::fake()->image('foo.png', 100, 200),
        );
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
    }
}

class UploadedFileWithCustomImageSizeMethod extends UploadedFile
{
    public function isValid(): bool
    {
        return true;
    }

    public function guessExtension(): string
    {
        return 'png';
    }

    public function dimensions()
    {
        return [200, 200];
    }
}
