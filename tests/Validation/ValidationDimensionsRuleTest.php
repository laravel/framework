<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\Dimensions;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationDimensionsRuleTest extends TestCase
{

    public function testWidth()
    {
        $rule = (new Dimensions)->width(100);

        $this->passes(
            $rule,
            width: 100,
            height: 100,
        );

        $this->fails(
            $rule,
            width: 99,
            height: 100,
            message: 'validation.width'
        );
    }

    public function testMinWidth()
    {
        $rule = (new Dimensions)->minWidth(100);

        $this->passes(
            $rule,
            width: 100,
            height: 100,
        );

        $this->fails(
            $rule,
            width: 99,
            height: 100,
            message: 'validation.min_width'
        );
    }

    public function testMaxWidth()
    {
        $rule = (new Dimensions)->maxWidth(100);

        $this->passes(
            $rule,
            width: 100,
            height: 100,
        );

        $this->fails(
            $rule,
            width: 101,
            height: 100,
            message: 'validation.max_width'
        );
    }

    public function testWidthBetween()
    {
        $rule = (new Dimensions)->widthBetween(100, 200);

        $this->passes(
            $rule,
            width: 100,
            height: 100,
        );

        $this->fails(
            $rule,
            width: 99,
            height: 100,
            message: 'validation.width_between'
        );
    }

    public function testHeight()
    {
        $rule = (new Dimensions)->height(100);

        $this->passes(
            $rule,
            width: 100,
            height: 100,
        );

        $this->fails(
            $rule,
            width: 100,
            height: 99,
            message: 'validation.height'
        );
    }

    public function testMinHeight()
    {
        $rule = (new Dimensions)->minHeight(100);

        $this->passes(
            $rule,
            width: 100,
            height: 100,
        );

        $this->fails(
            $rule,
            width: 100,
            height: 99,
            message: 'validation.min_height'
        );
    }


    public function testMaxHeight()
    {
        $rule = (new Dimensions)->maxHeight(100);

        $this->passes(
            $rule,
            width: 100,
            height: 100,
        );

        $this->fails(
            $rule,
            width: 100,
            height: 101,
            message: 'validation.max_height'
        );
    }

    public function testHeightBetween()
    {
        $rule = (new Dimensions)->heightBetween(100, 200);

        $this->passes(
            $rule,
            width: 100,
            height: 100,
        );

        $this->fails(
            $rule,
            width: 100,
            height: 99,
            message: 'validation.height_between'
        );
    }

    public function testRatio()
    {
        $rule = (new Dimensions)->ratio(1 / 2);

        $this->passes(
            $rule,
            width:100,
            height:200,
        );

        $this->fails(
            $rule,
            width:100,
            height:220,
            message: 'validation.ratio'
        );
    }

    public function testMinRatio()
    {
        $rule = (new Dimensions)->minRatio(1 / 2);

        $this->passes(
            $rule,
            width:100,
            height:200
        );

        $this->fails($rule,
            width: 100,
            height: 100,
            message: 'validation.min_ratio'
        );
    }

    public function testMaxRatio()
    {
        $rule = (new Dimensions)->maxRatio(1 / 1);

        $this->passes(
            $rule,
            width: 100,
            height: 100
        );

        $this->fails(
            $rule,
            width: 100,
            height: 200,
            message: 'validation.max_ratio'
        );
    }

    public function testRatioBetween()
    {
        $rule = (new Dimensions)->ratioBetween(1 / 2, 2 / 5);

        $this->passes(
            $rule,
            width: 100,
            height: 200
        );

        $this->fails(
            $rule,
            width: 100,
            height: 100,
            message: 'validation.ratio_between'
        );
    }

    public function testLegacyStringFormatIsSupported()
    {
        $rule = 'dimensions:min_width=100,max_width=200,min_height=100,max_height=200,ratio=1/1,min_ratio=1/1,max_ratio=2/5';

        $this->passes(
            $rule,
            width: 150,
            height: 150
        );

        $this->fails(
            $rule,
            width: 190,
            height: 210,
            message: 'validation.dimensions'
        );
     }

    public function fails($rule, $width, $height, $message)
    {
        $this->assertValidationRules(
            $rule,
            UploadedFile::fake()->image('image.jpg', $width, $height),
            false,
            [$message]
        );
    }

    public function passes($rule, $width, $height)
    {
        $this->assertValidationRules(
            $rule,
            UploadedFile::fake()->image('image.jpg', $width, $height),
            true,
            []
        );
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
