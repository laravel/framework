<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Dimensions;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ValidationDimensionsRuleTest extends TestCase
{
    #[Test]
    public function width_constraint()
    {
        $rule = Dimensions::defaults()->width(100);

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 100),
        );

        $this->fails(
            $rule,
            UploadedFile::fake()->image('image.jpg', 99, 101),
            messages: ['validation.width']
        );
    }

    #[Test]
    public function min_width_constraint()
    {
        $rule = Dimensions::defaults()->minWidth(100);

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 100),
        );

        $this->fails(
            $rule,
            UploadedFile::fake()->image('image.jpg', 99, 101),
            messages: ['validation.min_width']
        );
    }

    #[Test]
    public function max_width_constraint()
    {
        $rule = Dimensions::defaults()->maxWidth(100);

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 100),
        );

        $this->fails(
            $rule,
            UploadedFile::fake()->image('image.jpg', 101, 101),
            messages: ['validation.max_width']
        );
    }

    #[Test]
    public function width_between_constraint()
    {
        $rule = Dimensions::defaults()->widthBetween(100, 200);

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 100),
        );

        $this->fails(
            $rule,
            UploadedFile::fake()->image('image.jpg', 99, 201),
            messages: ['validation.width_between']
        );
    }

    #[Test]
    public function height_constraint()
    {
        $rule = Dimensions::defaults()->height(100);

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 100),
        );

        $this->fails(
            $rule,
            UploadedFile::fake()->image('image.jpg', 99, 101),
            messages: ['validation.height']
        );
    }

    #[Test]
    public function min_height_constraint()
    {
        $rule = Dimensions::defaults()->minHeight(100);

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 100),
        );

        $this->fails(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 99),
            messages: ['validation.min_height']
        );
    }

    #[Test]
    public function max_height_constraint()
    {
        $rule = Dimensions::defaults()->maxHeight(100);

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 100),
        );

        $this->fails(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 101),
            messages: ['validation.max_height'],
        );
    }

    #[Test]
    public function height_between_constraint()
    {
        $rule = Dimensions::defaults()->heightBetween(100, 200);

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 100),
        );

        $this->fails(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 99),
            messages: ['validation.height_between']
        );
    }

    #[test]
    public function ratio_constraint()
    {
        $rule = Dimensions::defaults()->ratio(1 / 2);

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 200),
        );

        $this->fails(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 100),
            messages: ['validation.ratio']
        );
    }

    #[Test]
    public function min_ratio_constraint()
    {
        $rule = Dimensions::defaults()->minRatio(1 / 2);

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 200),
        );

        $this->fails($rule,
            UploadedFile::fake()->image('image.jpg', 100, 100),
            messages: ['validation.min_ratio']
        );
    }

    #[Test]
    public function max_ratio_constraint()
    {
        $rule = Dimensions::defaults()->maxRatio(1 / 1);

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 100),
        );

        $this->fails(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 200),
            messages: ['validation.max_ratio']
        );
    }

    #[Test]
    public function ratio_between_constraint()
    {
        $rule = Dimensions::defaults()->ratioBetween(1 / 2, 2 / 5);

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 200),
        );

        $this->fails(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 100),
            messages: ['validation.ratio_between']
        );
    }

    #[Test]
    public function legacy_string_format_is_supported()
    {
        $rule = 'dimensions:min_width=100,max_width=200,min_height=100,max_height=200,ratio=1/1,min_ratio=1/1,max_ratio=2/5';

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 150, 150),
        );

        $this->fails(
            $rule,
            UploadedFile::fake()->image('image.jpg', 190, 210),
            messages: ['validation.dimensions']
        );
    }

    #[Test]
    public function legacy_string_based_format_with_custom_message()
    {
        $rule = 'dimensions:width=200,height=200';
        $message = 'Side of the image must be exactly 200px square';
        $translator = resolve('translator');

        $validator = new Validator(
            $translator,
            ['image' => UploadedFile::fake()->image('image.jpg', 100, 100)],
            ['image' => $rule],
            ['image' => $message]
        );

        $this->assertSame(
            expected: $message,
            actual: $validator->errors()->first('image')
        );
    }

    #[Test]
    public function legacy_constraints_passed_into_constructor_via_rule_supported()
    {
        $rule = Rule::dimensions([
            'min_width' => 100,
            'max_width' => 200,
            'min_height' => 100,
            'max_height' => 200,
            'ratio' => 1 / 1,
        ]);

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 150, 150),
        );

        $this->fails(
            $rule,
            UploadedFile::fake()->image('image.jpg', 190, 210),
            messages: ['validation.max_height', 'validation.ratio']
        );
    }

    #[Test]
    public function legacy_constructor_based_format_with_custom_message()
    {
        $rule = Rule::dimensions([
            'width' => 200,
            'height' => 200,
        ]);

        $message = 'Side of the image must be exactly 200px square';
        $translator = resolve('translator');

        $validator = new Validator(
            $translator,
            ['image' => UploadedFile::fake()->image('image.jpg', 100, 100)],
            ['image' => $rule],
            ['image' => $message]
        );

        $this->assertSame($message, $validator->errors()->first('image'));
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function custom_rules_added()
    {
        $this->passes(
            Dimensions::defaults()
                ->width(100)->height(100)
                ->rules(['mimes:jpg']),
            UploadedFile::fake()->image('image.jpg', 100, 100),
        );

        $this->fails(
            Dimensions::defaults()
                ->width(100)->height(100)
                ->rules(['mimes:png']),
            UploadedFile::fake()->image('image.jpg', 100, 100),
            messages: ['validation.mimes']
        );
    }

    #[Test]
    public function rule_is_macroable()
    {
        Dimensions::macro('thumbnail', function () {
            return $this->width(100)->height(100);
        });

        $rule = Dimensions::defaults()->thumbnail();

        $this->passes(
            $rule,
            UploadedFile::fake()->image('image.jpg', 100, 100),
        );

        $this->fails(
            $rule,
            UploadedFile::fake()->image('image.jpg', 99, 101),
            messages: ['validation.width', 'validation.height']
        );
    }

    public function fails(Dimensions|string $rule, UploadedFile $value, array $messages)
    {
        $this->assertValidationRules($rule, $value, false, $messages);
    }

    public function passes(Dimensions|string $rule, UploadedFile $value)
    {
        $this->assertValidationRules($rule, $value, true);
    }

    protected function assertValidationRules(Dimensions|string $rule, UploadedFile $values, $result, $messages = [])
    {
        $values = Arr::wrap($values);

        foreach ($values as $value) {
            $v = new Validator(
                resolve('translator'),
                ['my_file' => $value],
                ['my_file' => is_object($rule) ? clone $rule : $rule],
            );

            $this->assertSame($result, $v->passes());

            $this->assertSame(
                $result ? [] : ['my_file' => $messages],
                $v->messages()->toArray()
            );
        }
    }

    protected function setUp(): void
    {
        $container = Container::getInstance();

        $container->bind('translator', function () {
            return new Translator(new ArrayLoader, 'en');
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
